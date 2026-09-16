<?php

namespace App\Http\Controllers;

use App\Services\BanecoQrService;
use Illuminate\Http\Request;
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
