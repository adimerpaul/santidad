<?php

namespace App\Http\Controllers;

use App\Models\Sales;
use App\Services\BanecoQrService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QrPagoController extends Controller
{
    public function __construct(private readonly BanecoQrService $banecoQrService) {}

    public function generar(Request $request)
    {
        $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:200',
        ]);

        try {
            $transactionId = 'VTA' . now()->format('YmdHis') . rand(100, 999);

            $qr = $this->banecoQrService->generateQr(
                (float) $request->amount,
                $transactionId,
                $request->description ?? 'Venta Farmacia Santidad Divina'
            );

            return response()->json($qr);
        } catch (\Throwable $e) {
            Log::error('Error generando QR Baneco: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function estado($qrId)
    {
        try {
            return response()->json($this->banecoQrService->statusQr($qrId));
        } catch (\Throwable $e) {
            Log::error('Error consultando estado QR Baneco: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private const VENTA_RELACIONES = ['client:id,nombreRazonSocial,numeroDocumento', 'user:id,name', 'agencia:id,nombre'];

    /**
     * Pagos QR recibidos en el banco en un rango de fechas, cruzados con las ventas del sistema.
     * El banco solo consulta por día (paidQR/{fecha}), así que se recorre día por día.
     */
    public function pagados(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'fecha_fin'    => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
        ]);

        $inicio = Carbon::parse($request->fecha_inicio);
        $fin    = Carbon::parse($request->fecha_fin);

        if ($inicio->diffInDays($fin) > 30) {
            return response()->json(['message' => 'El rango máximo es de 31 días'], 422);
        }

        $pagos   = collect();
        $errores = [];
        foreach (CarbonPeriod::create($inicio, $fin) as $dia) {
            try {
                $pagos = $pagos->merge($this->banecoQrService->paidQr($dia->format('Y-m-d')));
            } catch (\Throwable $e) {
                Log::error('Error consultando QR pagados Baneco (' . $dia->format('Y-m-d') . '): ' . $e->getMessage());
                $errores[] = $dia->format('d/m/Y') . ': ' . $e->getMessage();
            }
        }

        $qrIds = $pagos->pluck('qrId')->filter()->unique()->values();

        $ventas = Sales::with(self::VENTA_RELACIONES)
            ->whereIn('qrId', $qrIds)
            ->get()
            ->keyBy('qrId');

        $pagos = $pagos->map(function ($pago) use ($ventas) {
            $pago['venta'] = $ventas->get($pago['qrId'] ?? null);
            return $pago;
        })->sortBy(fn ($p) => substr($p['paymentDate'] ?? '', 0, 10) . ' ' . ($p['paymentTime'] ?? ''))->values();

        // Ventas del rango registradas con QR que el banco no reporta como pagadas
        $ventasSinPago = Sales::with(self::VENTA_RELACIONES)
            ->whereBetween('fechaEmision', [$inicio->copy()->startOfDay(), $fin->copy()->endOfDay()])
            ->whereNotNull('qrId')
            ->where('qrId', '!=', '')
            ->whereNotIn('qrId', $qrIds)
            ->orderBy('fechaEmision')
            ->get();

        return response()->json([
            'pagos'         => $pagos,
            'ventasSinPago' => $ventasSinPago,
            'errores'       => $errores,
        ]);
    }

    /**
     * Ventas candidatas para vincular un pago QR: ventas de ingreso del día del pago
     * (o del texto buscado), ordenadas por cercanía al monto pagado.
     */
    public function ventasCandidatas(Request $request)
    {
        $request->validate([
            'fecha'  => 'required|date_format:Y-m-d',
            'monto'  => 'nullable|numeric',
            'search' => 'nullable|string|max:100',
        ]);

        $query = Sales::with(self::VENTA_RELACIONES)
            ->where('tipoVenta', 'Ingreso')
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '!=', 'ANULADO');
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('numeroFactura', $search)
                    ->orWhereHas('client', fn ($c) => $c->where('nombreRazonSocial', 'like', "%{$search}%")
                        ->orWhere('numeroDocumento', 'like', "%{$search}%"));
            });
        } else {
            $query->whereDate('fechaEmision', $request->fecha);
        }

        $monto = (float) $request->monto;

        $ventas = $query->orderByDesc('fechaEmision')->limit(200)->get()
            ->sortBy(fn ($v) => abs((float) $v->montoTotal - $monto))
            ->values();

        return response()->json($ventas);
    }

    /**
     * Vincula manualmente un pago QR a una venta. Si el QR estaba en otra venta, se le quita.
     */
    public function vincular(Request $request)
    {
        $request->validate([
            'qrId'    => 'required|string|max:50',
            'sale_id' => 'required|integer|exists:sales,id',
        ]);

        $venta = DB::transaction(function () use ($request) {
            Sales::where('qrId', $request->qrId)
                ->where('id', '!=', $request->sale_id)
                ->update(['qrId' => null]);

            $venta = Sales::findOrFail($request->sale_id);
            $venta->qrId = $request->qrId;
            $venta->save();

            return $venta;
        });

        Log::info("Pago QR {$request->qrId} vinculado manualmente a la venta {$venta->id} por el usuario {$request->user()->id}");

        return response()->json($venta->load(self::VENTA_RELACIONES));
    }

    /**
     * Quita el vínculo de un pago QR con su venta.
     */
    public function desvincular(Request $request)
    {
        $request->validate([
            'qrId' => 'required|string|max:50',
        ]);

        Sales::where('qrId', $request->qrId)->update(['qrId' => null]);

        Log::info("Pago QR {$request->qrId} desvinculado por el usuario {$request->user()->id}");

        return response()->json(['message' => 'Pago desvinculado']);
    }

    public function cancelar(Request $request)
    {
        $request->validate([
            'qrId' => 'required|string',
        ]);

        try {
            $this->banecoQrService->cancelQr($request->qrId);
            return response()->json(['message' => 'QR anulado correctamente']);
        } catch (\Throwable $e) {
            Log::error('Error anulando QR Baneco: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
