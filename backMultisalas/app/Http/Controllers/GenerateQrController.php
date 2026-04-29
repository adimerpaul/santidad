<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

class GenerateQrController extends Controller
{
    private string $baseUrl = 'https://apimkt.baneco.com.bo/ApiGateway';
    private string $aesKey = 'EAC24282B8DD481CA45D09BED45FC900';
    private string $userName = 'A56859645';
    private string $passwordPlain = '4920';
    private string $accountCredit = '4041488168';

//    private string $baseUrl = 'https://apimkt.baneco.com.bo/ApiGateway';
//    private string $aesKey = 'EAC24282B8DD481CA45D09BED45FC900';
//    private string $userName = 'A56859645';
//    private string $passwordPlain = '4920';
//    private string $accountCredit = '4041488168';
    private string $currency = 'BOB';
    private bool $singleUse = true;
    private bool $modifyAmount = false;
    private string $branchCode = 'E0001';

    public function generarQr(Request $request)
    {
        $payload = $request->validate([
            'client' => 'nullable|array',
            'client.nombreRazonSocial' => 'nullable|string',
            'client.numeroDocumento' => 'nullable|string',
            'montoTotal' => 'required|numeric|min:0.01',
            'tipoVenta' => 'nullable|in:BOLETERIA,CANDY',
        ]);

        try {
            $client = $payload['client'] ?? [];
            $amount = round((float) $payload['montoTotal'], 2);
            $tipoVenta = $payload['tipoVenta'] ?? 'CANDY';
            $tipoDescripcion = $tipoVenta === 'BOLETERIA' ? 'Boleteria' : 'Candy';
            $clientName = trim((string) ($client['nombreRazonSocial'] ?? 'CLIENTE'));
            $clientDocument = trim((string) ($client['numeroDocumento'] ?? ''));
            $description = $tipoDescripcion . ' ' . ($clientName !== '' ? $clientName : 'CLIENTE');
            if ($clientDocument !== '') {
                $description .= ' ' . $clientDocument;
            }

            $token = $this->authenticate();
            $accountCreditEncrypted = $this->encrypt($this->accountCredit);
            $transactionId = $tipoVenta . now()->format('YmdHis');

            $qrResponse = $this->post(
                '/api/qrsimple/generateQR',
                [
                    'transactionId' => $transactionId,
                    'accountCredit' => $accountCreditEncrypted,
                    'currency' => $this->currency,
                    'amount' => $amount,
                    'description' => substr($description, 0, 99),
                    'dueDate' => now()->addDays(7)->format('Y-m-d'),
                    'singleUse' => $this->singleUse,
                    'modifyAmount' => $this->modifyAmount,
                    'branchCode' => $this->branchCode,
                ],
                $token
            );

            if (($qrResponse['responseCode'] ?? null) !== 0) {
                return response()->json([
                    'message' => (string) ($qrResponse['message'] ?? 'No se pudo generar el QR.'),
                ], 422);
            }

            $qrImage = (string) ($qrResponse['qrImage'] ?? '');
            if ($qrImage === '') {
                return response()->json([
                    'message' => 'Baneco no devolvio la imagen del QR.',
                ], 422);
            }

            if (!str_starts_with($qrImage, 'data:image')) {
                $qrImage = 'data:image/png;base64,' . $qrImage;
            }

            return response()->json([
                'message' => (string) ($qrResponse['message'] ?? 'QR generado correctamente.'),
                'qr' => $qrImage,
                'qrId' => (string) ($qrResponse['qrId'] ?? ''),
                'transactionId' => $transactionId,
                'cliente' => $clientName,
                'documento' => $clientDocument,
                'monto' => number_format($amount, 2, '.', ''),
                'statusQrCode' => $qrResponse['statusQrCode'] ?? null,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al generar el QR: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function statusQr(string $qrId)
    {
        try {
            $token = $this->authenticate();
            $status = $this->getJson('/api/qrsimple/v2/statusQR/' . rawurlencode($qrId), $token);

            if (($status['responseCode'] ?? null) !== 0) {
                return response()->json([
                    'message' => (string) ($status['message'] ?? 'No se pudo consultar el estado del QR.'),
                    'statusQrCode' => $status['statusQrCode'] ?? $status['statusQRCode'] ?? null,
                ], 422);
            }

            return response()->json([
                'message' => (string) ($status['message'] ?? 'Consulta exitosa.'),
                'qrId' => $qrId,
                'statusQrCode' => (int) ($status['statusQrCode'] ?? $status['statusQRCode'] ?? -1),
                'payment' => $status['payment'] ?? [],
                'raw' => $status,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al verificar el QR: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancelarQr(string $qrId)
    {
        try {
            $token = $this->authenticate();

            $result = $this->tryCancelQr($qrId, $token);
            if ($result === null) {
                return response()->json([
                    'message' => 'Se detuvo el seguimiento local del QR. La anulacion remota no fue confirmada por la API.',
                    'qrId' => $qrId,
                    'cancelled' => false,
                ]);
            }

            return response()->json([
                'message' => (string) ($result['message'] ?? 'QR anulado correctamente.'),
                'qrId' => $qrId,
                'cancelled' => true,
                'raw' => $result,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al cancelar el QR: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function movimientosQr(Request $request)
    {
        $payload = $request->validate([
            'fecha' => 'required|date',
            'tipo' => 'nullable|in:TODOS,BOLETERIA,CANDY',
            'todo' => 'nullable|boolean',
        ]);

        $fecha = $payload['fecha'];
        $tipo = $payload['tipo'] ?? 'TODOS';

        try {
            $token = $this->authenticate();
            $result = $this->tryMovements($fecha, $token);

            if ($result === null) {
                return response()->json([
                    'message' => 'No se pudo obtener la lista de pagos desde Baneco con el endpoint 7.6 configurado.',
                ], 422);
            }

            return response()->json([
                'message' => 'Lista de pagos obtenida desde Baneco.',
                'fecha' => $fecha,
                'source' => 'baneco',
                'items' => $this->attachSalesToMovements($this->normalizeMovements($result, $fecha), $tipo),
                'raw' => $result,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error al consultar movimientos QR: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function ventasParaVincularQr(Request $request)
    {
        $payload = $request->validate([
            'fecha' => 'required|date',
            'tipo' => 'nullable|in:TODOS,BOLETERIA,CANDY',
        ]);

        $tipo = $payload['tipo'] ?? 'TODOS';

        $query = Sale::query()
            ->with(['user', 'client'])
            ->whereDate('fechaEmision', $payload['fecha'])
            ->where(function ($query) {
                $query->whereNull('qrId')
                    ->orWhere('qrId', '');
            })
            ->where(function ($query) {
                $query->whereNull('siatAnulado')
                    ->orWhere('siatAnulado', false);
            });

        if ($tipo !== 'TODOS') {
            $query->where('tipo', $tipo);
        }

        if (!$request->boolean('todo')) {
            $query->where('user_id', $request->user()->id);
        }

        return $query
            ->orderBy('id', 'desc')
            ->get()
            ->map(function (Sale $sale) {
                return [
                    'id' => $sale->id,
                    'tipo' => $sale->tipo,
                    'fechaEmision' => $sale->fechaEmision,
                    'montoTotal' => $sale->montoTotal,
                    'numeroFactura' => $sale->numeroFactura,
                    'venta' => $sale->venta,
                    'cliente' => $sale->client->nombreRazonSocial ?? '',
                    'usuario' => $sale->user->name ?? ($sale->usuario ?? ''),
                ];
            })
            ->values();
    }

    public function vincularVentaQr(Request $request)
    {
        $payload = $request->validate([
            'sale_id' => 'required|integer|exists:sales,id',
            'qrId' => 'required|string',
            'transactionId' => 'nullable|string',
            'fecha' => 'nullable|date',
        ]);

        $sale = Sale::findOrFail($payload['sale_id']);

        if (!empty($sale->qrId) && $sale->qrId !== $payload['qrId']) {
            return response()->json([
                'message' => 'La venta #' . $sale->id . ' ya tiene un pago QR vinculado.',
            ], 422);
        }

        $existingSale = Sale::query()
            ->where('id', '<>', $sale->id)
            ->where(function ($query) use ($payload) {
                $query->where('qrId', $payload['qrId']);

                if (!empty($payload['transactionId'])) {
                    $query->orWhere('qrTransactionId', $payload['transactionId']);
                }
            })
            ->first();

        if ($existingSale) {
            return response()->json([
                'message' => 'Este pago QR ya esta vinculado a la venta #' . $existingSale->id . '.',
            ], 422);
        }

        $sale->pagoQr = true;
        $sale->qrId = $payload['qrId'];
        $sale->qrTransactionId = $payload['transactionId'] ?? $sale->qrTransactionId;
        $sale->qrPagadoAt = !empty($payload['fecha']) ? $payload['fecha'] : now();
        $sale->credito = 'NO';
        $sale->save();

        return response()->json([
            'message' => 'Pago QR vinculado a la venta #' . $sale->id . '.',
            'sale' => [
                'id' => $sale->id,
                'tipo' => $sale->tipo,
                'montoTotal' => $sale->montoTotal,
                'qrId' => $sale->qrId,
                'qrTransactionId' => $sale->qrTransactionId,
            ],
        ]);
    }

    private function authenticate(): string
    {
        $passwordEncrypted = $this->encrypt($this->passwordPlain);
        $auth = $this->post('/api/authentication/authenticate', [
            'userName' => $this->userName,
            'password' => $passwordEncrypted,
        ]);

        if (($auth['responseCode'] ?? null) !== 0) {
            throw new \RuntimeException((string) ($auth['message'] ?? 'No se pudo autenticar con Baneco.'));
        }

        $token = (string) ($auth['token'] ?? '');
        if ($token === '') {
            throw new \RuntimeException('Baneco no devolvio token de autenticacion.');
        }

        return $token;
    }

    private function encrypt(string $text): string
    {
        $response = $this->banecoRequest()
            ->get(rtrim($this->baseUrl, '/') . '/api/authentication/encrypt', [
                'text' => $text,
                'aesKey' => $this->aesKey,
            ]);

        $this->throwIfFailed($response);

        return trim((string) $response->body(), "\" \r\n\t");
    }

    private function getJson(string $path, ?string $token = null): array
    {
        $request = $this->banecoRequest();
        if ($token !== null && $token !== '') {
            $request = $request->withToken($token);
        }

        $response = $request->get(rtrim($this->baseUrl, '/') . $path);
        $this->throwIfFailed($response);

        $decoded = $response->json();
        if (!is_array($decoded)) {
            throw new \RuntimeException('Respuesta JSON invalida del servicio QR.');
        }

        return $decoded;
    }

    private function post(string $path, array $body, ?string $token = null): array
    {
        $request = $this->banecoRequest()->contentType('application/json');

        if ($token !== null && $token !== '') {
            $request = $request->withToken($token);
        }

        $response = $request->post(rtrim($this->baseUrl, '/') . $path, $body);

        $this->throwIfFailed($response);

        $decoded = $response->json();
        if (!is_array($decoded)) {
            throw new \RuntimeException('Respuesta JSON invalida del servicio QR.');
        }

        return $decoded;
    }

    private function tryCancelQr(string $qrId, string $token): ?array
    {
        $candidates = [
            ['method' => 'post', 'path' => '/api/qrsimple/cancelQR', 'body' => ['qrId' => $qrId]],
            ['method' => 'post', 'path' => '/api/qrsimple/cancelQR/' . rawurlencode($qrId), 'body' => []],
            ['method' => 'get', 'path' => '/api/qrsimple/cancelQR/' . rawurlencode($qrId)],
            ['method' => 'post', 'path' => '/api/qrsimple/anularQR', 'body' => ['qrId' => $qrId]],
            ['method' => 'post', 'path' => '/api/qrsimple/anularQR/' . rawurlencode($qrId), 'body' => []],
            ['method' => 'get', 'path' => '/api/qrsimple/anularQR/' . rawurlencode($qrId)],
        ];

        foreach ($candidates as $candidate) {
            try {
                $result = $candidate['method'] === 'get'
                    ? $this->getJson($candidate['path'], $token)
                    : $this->post($candidate['path'], $candidate['body'], $token);

                $responseCode = $result['responseCode'] ?? null;
                if ($responseCode === 0 || $responseCode === '0') {
                    return $result;
                }
            } catch (Throwable $e) {
            }
        }

        return null;
    }

    private function tryMovements(string $fecha, string $token): ?array
    {
        $fechaBaneco = date('Ymd', strtotime($fecha));
        $result = $this->getJson('/api/qrsimple/v2/paidQR/' . rawurlencode($fechaBaneco), $token);

        $responseCode = $result['responseCode'] ?? null;
        if (
            $responseCode === 0 ||
            $responseCode === '0' ||
            isset($result['movements']) ||
            isset($result['payments']) ||
            isset($result['paymentList']) ||
            isset($result['paidQR']) ||
            isset($result['data'])
        ) {
            return $result;
        }

        return null;
    }

    private function normalizeMovements(array $result, string $fecha): array
    {
        $lists = [
            $result['paidQR'] ?? null,
            $result['movements'] ?? null,
            $result['movement'] ?? null,
            $result['payments'] ?? null,
            $result['paymentList'] ?? null,
            $result['transactions'] ?? null,
            $result['data'] ?? null,
            $result['items'] ?? null,
            $result['records'] ?? null,
        ];

        $items = null;
        foreach ($lists as $candidate) {
            if (is_array($candidate)) {
                $items = $candidate;
                break;
            }
        }

        if ($items === null) {
            $items = array_is_list($result) ? $result : [$result];
        }

        return collect($items)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) use ($fecha) {
                $paymentDate = $item['paymentDate'] ?? $item['date'] ?? $item['fecha'] ?? $fecha;
                $paymentTime = $item['paymentTime'] ?? $item['time'] ?? $item['hora'] ?? '';

                return [
                    'fecha' => $paymentDate,
                    'hora' => $paymentTime,
                    'fechaHora' => trim((string) $paymentDate . ' ' . (string) $paymentTime),
                    'qrId' => $item['qrId'] ?? $item['idQr'] ?? $item['qrID'] ?? $item['id'] ?? '',
                    'transactionId' => $item['transactionId'] ?? $item['transactionID'] ?? $item['reference'] ?? '',
                    'monto' => $item['amount'] ?? $item['paymentAmount'] ?? $item['monto'] ?? '',
                    'estado' => $item['status'] ?? $item['statusDescription'] ?? $item['statusQrCode'] ?? $item['statusQRCode'] ?? '',
                    'descripcion' => $item['description'] ?? $item['detail'] ?? $item['gloss'] ?? '',
                    'cliente' => $item['client'] ?? $item['customer'] ?? '',
                    'origen' => 'baneco',
                    'raw' => $item,
                ];
            })
            ->values()
            ->all();
    }

    private function attachSalesToMovements(array $items, string $tipo): array
    {
        $qrIds = collect($items)->pluck('qrId')->filter()->map(fn ($id) => (string) $id)->unique()->values();
        $transactionIds = collect($items)->pluck('transactionId')->filter()->map(fn ($id) => (string) $id)->unique()->values();

        if ($qrIds->isEmpty() && $transactionIds->isEmpty()) {
            return collect($items)
                ->map(function (array $item) {
                    $item['vinculado'] = false;
                    $item['ventaId'] = '';
                    $item['ventaTipo'] = '';
                    $item['ventaTotal'] = '';
                    $item['ventaFecha'] = '';
                    $item['ventaUsuario'] = '';

                    return $item;
                })
                ->filter(fn (array $item) => $tipo === 'TODOS')
                ->values()
                ->all();
        }

        $sales = Sale::query()
            ->with('user')
            ->where(function ($query) use ($qrIds, $transactionIds) {
                if ($qrIds->isNotEmpty()) {
                    $query->whereIn('qrId', $qrIds);
                }
                if ($transactionIds->isNotEmpty()) {
                    $qrIds->isNotEmpty()
                        ? $query->orWhereIn('qrTransactionId', $transactionIds)
                        : $query->whereIn('qrTransactionId', $transactionIds);
                }
            })
            ->get();

        $salesByQrId = $sales->filter(fn ($sale) => $sale->qrId)->keyBy(fn ($sale) => (string) $sale->qrId);
        $salesByTransactionId = $sales->filter(fn ($sale) => $sale->qrTransactionId)->keyBy(fn ($sale) => (string) $sale->qrTransactionId);

        return collect($items)
            ->map(function (array $item) use ($salesByQrId, $salesByTransactionId) {
                $sale = $salesByQrId->get((string) ($item['qrId'] ?? ''))
                    ?: $salesByTransactionId->get((string) ($item['transactionId'] ?? ''));

                $item['vinculado'] = $sale !== null;
                $item['ventaId'] = $sale->id ?? '';
                $item['ventaTipo'] = $sale->tipo ?? '';
                $item['ventaTotal'] = $sale->montoTotal ?? '';
                $item['ventaFecha'] = $sale->fechaEmision ?? '';
                $item['ventaUsuario'] = $sale->user->name ?? ($sale->usuario ?? '');

                return $item;
            })
            ->filter(function (array $item) use ($tipo) {
                return $tipo === 'TODOS' || ($item['ventaTipo'] ?? '') === $tipo;
            })
            ->values()
            ->all();
    }

    private function banecoRequest(): PendingRequest
    {
        return Http::timeout(30)
            ->acceptJson()
            ->withOptions([
                'verify' => false,
            ]);
    }

    private function throwIfFailed(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $message = trim((string) $response->body());
        throw new \RuntimeException(
            'HTTP ' . $response->status() . ($message !== '' ? ': ' . $message : '')
        );
    }
}
