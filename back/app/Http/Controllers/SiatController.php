<?php

namespace App\Http\Controllers;

use App\Models\Cufd;
use App\Models\Cuis;
use App\Services\SiatCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class SiatController extends Controller
{
    public function __construct(private readonly SiatCodeService $siatCodeService)
    {
    }

    public function dashboard()
    {
        $latestCuis = Cuis::latest('id')->limit(20)->get();
        $latestCufds = Cufd::latest('id')->limit(20)->get();

        return response()->json([
            'config' => [
                'nit' => config('siat.nit'),
                'codigo_sistema' => config('siat.codigo_sistema'),
                'codigo_modalidad' => config('siat.codigo_modalidad'),
                'codigo_ambiente' => config('siat.codigo_ambiente'),
                'url_rest' => config('siat.url_rest'),
                'token_masked' => $this->maskToken(config('siat.token')),
            ],
            'cuis' => $latestCuis,
            'cufds' => $latestCufds,
        ]);
    }

    public function cuisIndex()
    {
        return Cuis::latest('id')->get();
    }

    public function cufdIndex()
    {
        return Cufd::latest('id')->get();
    }

    public function generarCuis(Request $request)
    {
        $data = $request->validate([
            'codigo_sucursal' => 'nullable|integer|min:0',
            'codigo_punto_venta' => 'required|integer|min:0',
        ]);
//        verificar si hay fecha de vigecia el ultimo
        $ultimoCuis = Cuis::where('codigoSucursal', $data['codigo_sucursal'] ?? config('siat.codigo_sucursal'))
            ->where('codigoPuntoVenta', $data['codigo_punto_venta'])
            ->latest('id')
            ->first();
        if ($ultimoCuis && $ultimoCuis->fechaVigencia && $ultimoCuis->fechaVigencia->isFuture()) {
            return response()->json([
                'message' => 'Ya existe un CUIS vigente para este punto de venta',
                'cuis' => $ultimoCuis,
            ], 422);
        }
        $payload = [
            'codigoAmbiente' => (int) config('siat.codigo_ambiente'),
            'codigoModalidad' => (int) config('siat.codigo_modalidad'),
            'codigoPuntoVenta' => (int) $data['codigo_punto_venta'],
            'codigoSistema' => (string) config('siat.codigo_sistema'),
            'codigoSucursal' => (int) ($data['codigo_sucursal'] ?? config('siat.codigo_sucursal')),
            'nit' => (int) config('siat.nit'),
        ];

        try {
            $cuis = null;
            $response = $this->siatCodeService->solicitarCuis($payload);
            $normalized = $response['RespuestaCuis'] ?? $response;
            $codigo = $normalized['codigo'] ?? null;

            if (!$codigo) {
                return response()->json([
                    'message' => 'SIAT no devolvió un código CUIS válido',
                    'response' => $response,
                ], 422);
            }

            DB::transaction(function () use ($payload, $normalized, &$cuis) {
                $cuis = Cuis::create([
                    'codigo' => $normalized['codigo'],
                    'fechaVigencia' => $normalized['fechaVigencia'] ?? null,
                    'fechaCreacion' => now(),
                    'codigoPuntoVenta' => $payload['codigoPuntoVenta'],
                    'codigoSucursal' => $payload['codigoSucursal'],
                ]);
            });

            return response()->json($cuis, 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'No se pudo generar el CUIS desde SIAT',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function generarCufd(Request $request)
    {
        $data = $request->validate([
            'codigo_sucursal' => 'nullable|integer|min:0',
            'codigo_punto_venta' => 'required|integer|min:0',
        ]);

        $codigoSucursal = (int) ($data['codigo_sucursal'] ?? config('siat.codigo_sucursal'));
        $codigoPuntoVenta = (int) $data['codigo_punto_venta'];

        $cuis = Cuis::where('codigoSucursal', $codigoSucursal)
            ->where('codigoPuntoVenta', $codigoPuntoVenta)
            ->latest('id')
            ->first();

        if (!$cuis) {
            return response()->json([
                'message' => 'Primero debes generar o seleccionar un CUIS activo para ese punto de venta',
            ], 422);
        }
        $ultimoCufd = Cufd::where('codigoSucursal', $codigoSucursal)
            ->where('codigoPuntoVenta', $codigoPuntoVenta)
            ->latest('id')
            ->first();

        if ($ultimoCufd && $ultimoCufd->fechaVigencia && $ultimoCufd->fechaVigencia->isFuture()) {
            return response()->json([
                'message' => 'Ya existe un CUFD vigente para este punto de venta',
                'cufd' => $ultimoCufd,
            ], 422);
        }

        $payload = [
            'codigoAmbiente' => (int) config('siat.codigo_ambiente'),
            'codigoModalidad' => (int) config('siat.codigo_modalidad'),
            'codigoPuntoVenta' => $codigoPuntoVenta,
            'codigoSistema' => (string) config('siat.codigo_sistema'),
            'codigoSucursal' => $codigoSucursal,
            'cuis' => $cuis->codigo,
            'nit' => (int) config('siat.nit'),
        ];

        try {
            $cufd = null;
            $response = $this->siatCodeService->solicitarCufd($payload);
            $normalized = $response['RespuestaCufd'] ?? $response;
            $codigo = $normalized['codigo'] ?? null;

            if (!$codigo) {
                return response()->json([
                    'message' => 'SIAT no devolvió un código CUFD válido',
                    'response' => $response,
                ], 422);
            }

            DB::transaction(function () use ($payload, $normalized, &$cufd) {
                $cufd = Cufd::create([
                    'codigo' => $normalized['codigo'],
                    'codigoControl' => $normalized['codigoControl'] ?? null,
                    'direccion' => config('app.url'),
                    'fechaVigencia' => now()->endOfDay(),
                    'fechaCreacion' => now(),
                    'codigoPuntoVenta' => $payload['codigoPuntoVenta'],
                    'codigoSucursal' => $payload['codigoSucursal'],
                ]);
            });

            return response()->json($cufd, 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'No se pudo generar el CUFD desde SIAT',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function maskToken(?string $token): string
    {
        if (!$token) {
            return '';
        }

        $length = strlen($token);

        if ($length <= 10) {
            return str_repeat('*', $length);
        }

        return substr($token, 0, 6) . str_repeat('*', max($length - 10, 0)) . substr($token, -4);
    }
}
