<?php

namespace App\Services;

use App\Models\Sales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleRequestService
{
    private function fingerprint(Request $request): string
    {
        $sort = function (array $data) use (&$sort): array {
            if (!array_is_list($data)) ksort($data);
            foreach ($data as &$value) if (is_array($value)) $value = $sort($value);
            return $data;
        };
        return hash('sha256', json_encode($sort($request->except('request_id')), JSON_THROW_ON_ERROR));
    }

    private function verify(Request $request, object $row): void
    {
        abort_unless((int) $row->user_id === (int) $request->user()->id, 403);
        abort_unless(hash_equals($row->request_hash, $this->fingerprint($request)), 409, 'Este intento ya corresponde a otros datos. Comprueba la venta registrada antes de continuar.');
    }

    public function existing(Request $request)
    {
        if (!$request->request_id) return null;
        $row = DB::table('sale_requests')->find($request->request_id);
        if (!$row) return null;
        $this->verify($request, $row);
        return $row->sale_id ? $this->response($row) : null;
    }

    // Called inside the same transaction that creates the sale and adjusts stock.
    // The unique UUID serializes concurrent attempts before invoice numbering.
    public function claim(Request $request)
    {
        if (!$request->request_id) return null;
        DB::table('sale_requests')->insertOrIgnore([
            'id' => $request->request_id, 'user_id' => $request->user()->id,
            'request_hash' => $this->fingerprint($request), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $row = DB::table('sale_requests')->where('id', $request->request_id)->lockForUpdate()->first();
        $this->verify($request, $row);
        return $row->sale_id ? $this->response($row) : null;
    }

    public function record(Request $request, int $saleId): void
    {
        if ($request->request_id) DB::table('sale_requests')->where('id', $request->request_id)->update(['sale_id' => $saleId, 'updated_at' => now()]);
    }

    public function finish(Request $request, ?string $warning): void
    {
        if ($request->request_id) DB::table('sale_requests')->where('id', $request->request_id)->update(['finished_at' => now(), 'warning' => $warning, 'updated_at' => now()]);
    }

    public function status(Request $request, string $id)
    {
        $row = DB::table('sale_requests')->find($id);
        abort_unless($row && $row->sale_id, 404, 'La solicitud todavía no tiene una venta confirmada.');
        abort_unless((int) $row->user_id === (int) $request->user()->id, 403);
        return $this->response($row);
    }

    private function response(object $row)
    {
        $sale = Sales::withTrashed()->with(['details.product', 'client', 'agencia'])->findOrFail($row->sale_id);
        // An annulled or archived sale is still the result of this attempt.
        $sale->setAttribute('procesamiento_pendiente', !$row->finished_at);
        $sale->setAttribute('solicitud_repetida', true);
        $sale->setAttribute('advertencia_procesamiento', $row->warning);
        return response()->json($sale)->header('Cache-Control', 'no-store')->header('X-Sale-Replayed', 'true');
    }
}
