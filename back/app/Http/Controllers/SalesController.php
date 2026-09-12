<?php

namespace App\Http\Controllers;

use App\Mail\FacturaEstadoMail;
use App\Mail\FacturaVentaMail;
use App\Models\Buy;
use App\Models\Client;
use App\Models\Cufd;
use App\Models\Detail;
use App\Models\Product;
use App\Models\Sales;
use App\Http\Requests\StoreSalesRequest;
use App\Http\Requests\UpdateSalesRequest;
use App\Services\FacturacionSiatService;
use App\Services\PromotionPricingService;
use App\Models\CashClosure;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SalesController extends Controller
{
    public function __construct(
        private readonly FacturacionSiatService $facturacionService,
        private readonly PromotionPricingService $promotionPricing,
    ) {}

    public function index()
    {
        return Sales::all();
    }

    public function show(Sales $sales)
    {
        return $sales;
    }

    public function destroy(Sales $sales)
    {
        return $sales->delete();
    }

    // ─────────────────────────── Crear venta ───────────────────────────

    public function store(StoreSalesRequest $request)
    {
        // Verificar que la caja esté abierta antes de procesar cualquier venta
        $user = $request->user();
        if ($user && $user->agencia_id && (string)$user->id !== '1') {
            $cajaAbierta = CashClosure::where('agencia_id', $user->agencia_id)
                ->where('user_id', $user->id)
                ->where('estado', 'ABIERTO')
                ->exists();

            if (!$cajaAbierta) {
                return response()->json([
                    'message' => 'Usted no cuenta con un turno de caja abierto a su nombre. Debe aperturar su turno para vender.',
                ], 400);
            }
        }

        error_log('cliente numeroDocumento: ' . $request->client['numeroDocumento']);

        if ($request->client['numeroDocumento'] !== '0') {
            if (!Cufd::where('fechaVigencia', '>', date('Y-m-d H:i:s'))->exists()) {
                return response()->json([
                    'message' => 'El cliente tiene que tener un CUFD registrado para poder realizar la venta',
                ], 400);
            }
        }

        DB::beginTransaction();
        try {
            // Solo el administrador puede vender a nombre de otra agencia (es el
            // único con el selector habilitado). Para el resto se impone la
            // agencia del usuario: si el navegador manda un id viejo, la venta
            // se registraría en otra sucursal y facturaría donde no corresponde.
            $usuario = $request->user();
            $agencia_id = (string) $usuario->id === '1' || !$usuario->agencia_id
                ? (int) $request->agencia_id
                : (int) $usuario->agencia_id;
            $productosProcesados = [];
            $montoBaseCentavos = 0;
            $descuentoProductoCentavos = 0;
            $productIds = collect($request->products)->pluck('id')->sort()->values();
            $productosBloqueados = Product::whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // La base de datos define el precio unitario efectivo, ya redondeado
            // a Bs 0,10. La canasta y el detalle usan exactamente ese precio.
            foreach ($request->products as $product) {
                $productModel = $productosBloqueados->get($product['id']);
                if (!$productModel) {
                    throw new \DomainException('Uno de los productos ya no se encuentra disponible.');
                }
                $cantidad = (int) $product['cantidadPedida'];
                $campoStock = "cantidadSucursal{$agencia_id}";
                $stockDisponible = (float) ($productModel->getAttribute($campoStock) ?? 0);

                if ($stockDisponible < $cantidad || (float) $productModel->cantidad < $cantidad) {
                    throw new \DomainException('No hay suficiente stock del producto ' . $productModel->nombre);
                }

                $pricing = $this->promotionPricing->resolve($productModel, 'physical', $agencia_id);
                $precioOriginalCentavos = Money::toCents($pricing['precio_original']);
                $precioVenta = $pricing['precio_venta'];
                $precioVentaCentavos = Money::toCents($precioVenta);
                if ($precioVentaCentavos <= 0) {
                    throw new \DomainException('El precio de venta de ' . $productModel->nombre . ' debe ser mayor a 0.');
                }

                $subtotalCentavos = $cantidad * $precioVentaCentavos;
                $descuentoLineaCentavos = $cantidad * ($precioOriginalCentavos - $precioVentaCentavos);
                $montoBaseCentavos += $subtotalCentavos;
                $descuentoProductoCentavos += $descuentoLineaCentavos;

                $productosProcesados[] = [
                    'datos' => $product,
                    'modelo' => $productModel,
                    'cantidad' => $cantidad,
                    'precioVenta' => Money::fromCents($precioVentaCentavos),
                    'subtotal' => Money::fromCents($subtotalCentavos),
                    'pricing' => $pricing,
                ];
            }

            $aporteCentavos = Money::toCents($request->aporte ?? 0);
            $descuentoCentavos = Money::toCents($request->descuento ?? 0);
            if ($descuentoCentavos > $montoBaseCentavos + $aporteCentavos) {
                throw new \DomainException('El descuento no puede ser mayor al total de la venta.');
            }
            $montoCalculadoCentavos = $montoBaseCentavos + $aporteCentavos - $descuentoCentavos;
            $montoTotalCentavos = Money::roundCentsToTenth($montoCalculadoCentavos);

            $aporte = Money::fromCents($aporteCentavos);
            $descuento = Money::fromCents($descuentoCentavos);
            $montoCalculado = Money::fromCents($montoCalculadoCentavos);
            $montoTotal = Money::fromCents($montoTotalCentavos);
            $ajusteRedondeo = Money::fromCents($montoTotalCentavos - $montoCalculadoCentavos);

            $montoEfectivoVal = 0.0;
            $montoQrVal = 0.0;

            if ($request->metodoPago === 'Personalizado') {
                $montoEfectivoCentavos = Money::toCents($request->montoEfectivo ?? 0);
                $montoQrCentavos = Money::toCents($request->montoQr ?? 0);
                if ($montoEfectivoCentavos + $montoQrCentavos !== $montoTotalCentavos) {
                    throw new \DomainException('La suma del pago en efectivo y QR debe ser igual al total de la venta.');
                }
                $montoEfectivoVal = Money::fromCents($montoEfectivoCentavos);
                $montoQrVal = Money::fromCents($montoQrCentavos);
            } elseif ($request->metodoPago === 'Efectivo') {
                $montoEfectivoVal = $montoTotal;
                $montoQrVal = 0.0;
            } else {
                // 'Qr', 'Tarjeta', 'Transferencia'
                $montoQrVal = $montoTotal;
                $montoEfectivoVal = 0.0;
            }

            $client = $this->insertUpdateClient($request);

            $sale = new Sales();
            $sale->fill([
                'numeroFactura' => 0,
                'fechaEmision'  => date('Y-m-d H:i:s'),
                'montoTotal'    => $montoTotal,
                'montoCalculado'=> $montoCalculado,
                'ajusteRedondeo'=> $ajusteRedondeo,
                'usuario'       => $request->user()->name,
                'venta'         => 'R',
                'tipoVenta'     => 'Ingreso',
                'metodoPago'    => $request->metodoPago,
                'montoEfectivo' => $montoEfectivoVal,
                'montoQr'       => $montoQrVal,
                'qrId'          => $request->qrId,
                'client_id'     => $client->id,
                'aporte'        => $aporte,
                'descuento'     => $descuento,
                'user_id'       => $request->user()->id,
                'agencia_id'    => $agencia_id,
            ]);
            $sale->save();

            $concepto = '';

            foreach ($productosProcesados as $productoProcesado) {
                $product = $productoProcesado['datos'];
                $productSale = $productoProcesado['modelo'];
                $cantidad = $productoProcesado['cantidad'];
                $precioVenta = $productoProcesado['precioVenta'];
                $detail = new Detail();
                $detail->fill([
                    'cantidad'       => $cantidad,
                    'precioUnitario' => $precioVenta,
                    'precioOriginal' => $productoProcesado['pricing']['precio_original'],
                    'promocion_id' => $productoProcesado['pricing']['promocion_id'],
                    'promocion_nombre' => $productoProcesado['pricing']['promocion_nombre'],
                    'promocion_porcentaje' => $productoProcesado['pricing']['porcentaje'],
                    'subTotal'       => $productoProcesado['subtotal'],
                    'sale_id'        => $sale->id,
                    'descripcion'    => $productSale->nombre,
                    'user_id'        => $request->user()->id,
                    'product_id'     => $productSale->id,
                ]);
                $detail->save();

                $concepto .= $cantidad . $productSale->nombre . ',';

                $productSale->cantidad -= $cantidad;
                $this->ajustarStockSucursal($productSale, $agencia_id, -$cantidad);
                $productSale->save();

                foreach (($product['buys'] ?? []) as $buy) {
                    if (isset($buy['cantidadAVender']) && $buy['cantidadAVender'] > 0) {
                        $buyModel                   = Buy::lockForUpdate()->findOrFail($buy['id']);
                        $cantidadLote = (float) $buy['cantidadAVender'];
                        if ((int) $buyModel->product_id !== (int) $productSale->id || (float) $buyModel->cantidadVendida < $cantidadLote) {
                            throw new \DomainException('El lote seleccionado ya no tiene stock suficiente para ' . $productSale->nombre . '.');
                        }
                        $buyModel->cantidadVendida -= $cantidadLote;
                        $buyModel->save();
                    }
                }
            }

            $sale->concepto           = rtrim($concepto, ',');
            $sale->descuento_producto = Money::fromCents($descuentoProductoCentavos);
            $sale->save();

            DB::commit();
        } catch (\DomainException $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }

        // SIAT y correo fuera de la transacción; un fallo no cancela la venta
        $online = $this->facturacionService->procesar($sale);

        // El correo solo sale cuando la venta llegó a ser factura. Las notas de
        // venta (cliente sin NIT, o agencia con sucursal 0) no envían nada.
        if ($sale->venta === 'F' && (int) $sale->numeroFactura > 0) {
            $this->enviarCorreoFactura($sale, $online);
        }

        return Sales::with(['details.product', 'client', 'agencia'])->find($sale->id);
    }

    // ─────────────────────────── Actualizar venta ───────────────────────────

    public function update(UpdateSalesRequest $request, Sales $sale)
    {
        try {
            DB::beginTransaction();
            $agencia_id = $sale->agencia_id;

            // Restaurar stock anterior
            foreach ($sale->details as $detail) {
                $product            = Product::find($detail->product_id);
                $product->cantidad += $detail->cantidad;
                $this->ajustarStockSucursal($product, $agencia_id, $detail->cantidad);
                $product->save();
            }

            Detail::whereSaleId($sale->id)->delete();

            $montoCalculadoCentavos = 0;
            $concepto   = '';

            foreach ($request->details as $product) {
                $precioUnitario = Money::roundToCents($product['precioUnitario']);
                $subTotalCentavos = Money::toCents((float) $product['cantidad'] * $precioUnitario);
                $subTotal = Money::fromCents($subTotalCentavos);
                $concepto   .= $product['cantidad'] . $product['descripcion'] . ',';
                $montoCalculadoCentavos += $subTotalCentavos;

                $detail = new Detail();
                $detail->fill([
                    'cantidad'       => $product['cantidad'],
                    'precioUnitario' => $precioUnitario,
                    'subTotal'       => $subTotal,
                    'sale_id'        => $sale->id,
                    'descripcion'    => $product['descripcion'],
                    'user_id'        => $request->user()->id,
                    'product_id'     => $product['product_id'],
                ]);
                $detail->save();

                $productSale            = Product::find($product['product_id']);
                $productSale->cantidad -= $product['cantidad'];
                $this->ajustarStockSucursal($productSale, $agencia_id, -$product['cantidad']);
                $productSale->save();
            }

            $montoTotalCentavos = Money::roundCentsToTenth($montoCalculadoCentavos);
            $sale->montoCalculado = Money::fromCents($montoCalculadoCentavos);
            $sale->ajusteRedondeo = Money::fromCents($montoTotalCentavos - $montoCalculadoCentavos);
            $sale->montoTotal = Money::fromCents($montoTotalCentavos);
            $sale->concepto   = rtrim($concepto, ',');
            $sale->modificado = 'SI';
            $sale->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────── Anular venta ───────────────────────────

    public function salesAnular($id, Request $request)
    {
        $sale = Sales::find($id);

        try {
            $this->facturacionService->anular($sale);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $sale->estado = 'ANULADO';
        $sale->save();

        if ($sale->tipoVenta === 'Ingreso') {
            foreach (Detail::whereSaleId($id)->get() as $detail) {
                $product            = Product::find($detail->product_id);
                $product->cantidad += $detail->cantidad;
                $this->ajustarStockSucursal($product, $sale->agencia_id, $detail->cantidad);
                $product->save();
            }
        }

        $this->enviarCorreoEstado($sale, 'anulacion');
    }

    // ─────────────────────────── Revertir anulación ───────────────────────────

    public function salesRevertir($id)
    {
        $sale = Sales::find($id);

        if (!$sale) {
            return response()->json(['message' => 'Venta no encontrada'], 404);
        }

        if ($sale->estado !== 'ANULADO') {
            return response()->json(['message' => 'La venta no está anulada'], 422);
        }

        try {
            $this->facturacionService->revertirAnulacion($sale);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $sale->estado = 'ACTIVO';
        $sale->save();

        if ($sale->tipoVenta === 'Ingreso') {
            foreach (Detail::whereSaleId($id)->get() as $detail) {
                $product            = Product::find($detail->product_id);
                $product->cantidad -= $detail->cantidad;
                $this->ajustarStockSucursal($product, $sale->agencia_id, -$detail->cantidad);
                $product->save();
            }
        }

        $this->enviarCorreoEstado($sale, 'reversion');
    }

    // ─────────────────────────── Envío por paquete ───────────────────────────

    public function salesEnviarPaquete($id)
    {
        $sale = Sales::find($id);

        if (!$sale) {
            return response()->json(['message' => 'Venta no encontrada'], 404);
        }

        if ($sale->siatEnviado) {
            return response()->json(['message' => 'La factura ya fue enviada a SIAT'], 422);
        }

        if ($sale->estado === 'ANULADO') {
            return response()->json(['message' => 'No se puede enviar una factura anulada'], 422);
        }

        try {
            $this->facturacionService->enviarPaquete($sale);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Factura enviada y validada correctamente en SIAT']);
    }

    // ─────────────────────────── Gastos ───────────────────────────

    public function salesGasto(StoreSalesRequest $request)
    {
        if ($request->concepto == '') {
            $numeroGasto       = Sales::where('tipoVenta', 'Egreso')->count();
            $request->concepto = 'Gasto ' . ($numeroGasto + 1);
        }

        $montoEfectivoVal = 0.0;
        $montoQrVal = 0.0;
        if ($request->metodoPago === 'Efectivo') {
            $montoEfectivoVal = (double) $request->montoTotal;
        } else {
            $montoQrVal = (double) $request->montoTotal;
        }

        $sale = new Sales();
        $sale->fill([
            'numeroFactura' => 0,
            'fechaEmision'  => date('Y-m-d H:i:s'),
            'montoTotal'    => $request->montoTotal,
            'usuario'       => $request->user()->name,
            'concepto'      => $request->concepto,
            'tipoVenta'     => 'Egreso',
            'metodoPago'    => $request->metodoPago,
            'montoEfectivo' => $montoEfectivoVal,
            'montoQr'       => $montoQrVal,
            'client_id'     => $request->client_id == 0 ? null : $request->client_id,
            'user_id'       => $request->user()->id,
        ]);
        $sale->save();

        $detail = new Detail();
        $detail->fill([
            'cantidad'       => 1,
            'precioUnitario' => $request->montoTotal,
            'subTotal'       => $request->montoTotal,
            'sale_id'        => $sale->id,
            'descripcion'    => $request->concepto,
            'user_id'        => $request->user()->id,
        ]);
        $detail->save();

        return Sales::with(['details', 'client', 'agencia'])->find($sale->id);
    }

    // ─────────────────────────── Reportes ───────────────────────────

    /**
     * Normaliza la fecha que llega por URL a 'Y-m-d H:i:s'.
     * El front envía inputs datetime-local ('2026-09-11T19:02'), por lo que no se
     * puede concatenar la hora a ciegas: quedaría '2026-09-11T19:02 23:59:59',
     * que MySQL no interpreta y hace que la consulta no devuelva ninguna fila.
     */
    private function normalizarFechaFiltro($valor, bool $finDeRango): string
    {
        $valor = trim(str_replace('T', ' ', (string) $valor));

        try {
            $fecha = Carbon::parse($valor);
        } catch (\Throwable $e) {
            $fecha = Carbon::now();
        }

        // Solo fecha (sin hora): se cubre el día completo
        if (!preg_match('/\d{1,2}:\d{2}/', $valor)) {
            return ($finDeRango ? $fecha->endOfDay() : $fecha->startOfDay())->format('Y-m-d H:i:s');
        }

        // Fecha con hora pero sin segundos: el límite final llega hasta el segundo 59
        if ($finDeRango && !preg_match('/\d{1,2}:\d{2}:\d{2}/', $valor)) {
            $fecha->second(59);
        }

        return $fecha->format('Y-m-d H:i:s');
    }

    public function betweenDates($fechaInicio, $fechaFin, Request $request)
    {
        $fechaInicio = $this->normalizarFechaFiltro($fechaInicio, false);
        $fechaFin    = $this->normalizarFechaFiltro($fechaFin, true);

        $query = Sales::whereBetween('fechaEmision', [$fechaInicio, $fechaFin])
            ->with(['details', 'client', 'user', 'agencia'])
            ->orderBy('fechaEmision', 'desc');

        if ($request->agencia && $request->agencia != 0) {
            $query->where('agencia_id', $request->agencia);
        }
        if ($request->user && $request->user != 0) {
            $query->where('user_id', $request->user);
        }

        return $query->get();
    }

    public function reportTotal($fechaInicio, $fechaFin)
    {
        return Sales::whereBetween('fechaEmision', [$this->normalizarFechaFiltro($fechaInicio, false), $this->normalizarFechaFiltro($fechaFin, true)])
            ->where('estado', '!=', 'ANULADO')
            ->with('user')
            ->get();
    }

    public function reportTotalIngreso($fechaInicio, $fechaFin)
    {
        return Sales::whereBetween('fechaEmision', [$this->normalizarFechaFiltro($fechaInicio, false), $this->normalizarFechaFiltro($fechaFin, true)])
            ->where('estado', '!=', 'ANULADO')
            ->where('tipoVenta', 'Ingreso')
            ->with('user')
            ->get();
    }

    public function reportTotalEgreso($fechaInicio, $fechaFin)
    {
        return Sales::whereBetween('fechaEmision', [$this->normalizarFechaFiltro($fechaInicio, false), $this->normalizarFechaFiltro($fechaFin, true)])
            ->where('estado', '!=', 'ANULADO')
            ->where('tipoVenta', 'Egreso')
            ->with('user')
            ->get();
    }

    public function topSellers(Request $request)
    {
        $days      = max((int) $request->get('days', 1), 1);
        $agenciaId = $request->get('agencia_id');
        $start     = Carbon::now()->subDays($days - 1)->startOfDay();
        $end       = Carbon::now()->endOfDay();

        $base = DB::table('details as d')
            ->join('sales as s', 's.id', '=', 'd.sale_id')
            ->whereBetween('s.fechaEmision', [$start, $end])
            ->where('s.estado', '!=', 'ANULADO')
            ->where('s.tipoVenta', 'Ingreso');

        if (!empty($agenciaId)) {
            $base->where('s.agencia_id', $agenciaId);
        }

        $rows = $base
            ->select('d.product_id', DB::raw('SUM(d.cantidad) as cantidad_total'))
            ->groupBy('d.product_id')
            ->orderByDesc('cantidad_total')
            ->limit(20)
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([]);
        }

        $ids      = $rows->pluck('product_id')->all();
        $products = Product::query()
            ->whereIn('id', $ids)
            ->select('id', 'nombre', 'imagen', 'precio', 'porcentaje', 'cantidad', 'category_id')
            ->get()
            ->map(function ($p) {
                if (!$p->imagen || !file_exists(public_path('/images/' . $p->imagen))) {
                    $p->imagen = 'productDefault.jpg';
                }
                return $p;
            })
            ->keyBy('id');

        return response()->json(
            $rows->map(function ($r) use ($products, $agenciaId) {
                $p = $products[$r->product_id] ?? null;
                if (!$p) {
                    return null;
                }

                $pricing = $this->promotionPricing->resolve(
                    $p,
                    'web',
                    $agenciaId ? (int) $agenciaId : null
                );
                $precio = $pricing['precio_venta'];
                $precioNormal = $pricing['porcentaje'] > 0 ? $pricing['precio_original'] : null;

                return [
                    'id'           => (int) $p->id,
                    'nombre'       => $p->nombre,
                    'imagen'       => $p->imagen ?: 'productDefault.jpg',
                    'precio'       => number_format($precio, 2, '.', ''),
                    'precioNormal' => $precioNormal ? number_format($precioNormal, 2, '.', '') : null,
                    'porcentaje'   => (float) $pricing['porcentaje'],
                    'promocionId'  => $pricing['promocion_id'],
                    'promocion'    => $pricing['promocion_nombre'],
                    'cantidad'     => (int) ($p->cantidad ?? 0),
                    'vendido'      => (int) $r->cantidad_total,
                ];
            })->filter()->values()
        );
    }

    // ─────────────────────────── Helpers ───────────────────────────

    private function enviarCorreoFactura(Sales $sale, bool $online): void
    {
        try {
            $sale->loadMissing('client');
            $email = $sale->client?->email;
            if ($email) {
                Mail::to($email)->send(new FacturaVentaMail([
                    'sale_id' => $sale->id,
                    'online'  => $online,
                ]));
            }
        } catch (\Throwable $e) {
            error_log('Error enviando correo factura: ' . $e->getMessage());
        }
    }

    private function enviarCorreoEstado(Sales $sale, string $tipo): void
    {
        try {
            $sale->loadMissing('client');
            $email = $sale->client?->email;
            if ($email) {
                Mail::to($email)->send(new FacturaEstadoMail($sale, $tipo));
            }
        } catch (\Throwable $e) {
            error_log("Error enviando correo {$tipo}: " . $e->getMessage());
        }
    }

    private function ajustarStockSucursal(Product $product, int $agenciaId, float $delta): void
    {
        if ($agenciaId < 1) {
            return;
        }
        $field   = "cantidadSucursal{$agenciaId}";
        $current = (float) $product->getAttribute($field);
        if ($current !== null) {
            $product->setAttribute($field, $current + $delta);
        }
    }

    public function insertUpdateClient(StoreSalesRequest $request): Client
    {
        $complemento = $request->client['complemento'] ?? '';

        $query = Client::where('numeroDocumento', $request->client['numeroDocumento'])
            ->where('complemento', $complemento);

        if ($query->exists()) {
            $client                              = $query->first();
            $client->nombreRazonSocial           = $request->client['nombreRazonSocial'];
            $client->codigoTipoDocumentoIdentidad = $request->client['codigoTipoDocumentoIdentidad'];
            $client->email                       = $request->client['email'];
            $client->save();
            return $client;
        }

        $client                              = new Client();
        $client->nombreRazonSocial           = $request->client['nombreRazonSocial'];
        $client->codigoTipoDocumentoIdentidad = $request->client['codigoTipoDocumentoIdentidad'];
        $client->numeroDocumento             = $request->client['numeroDocumento'];
        $client->complemento                 = strtoupper($complemento);
        $client->email                       = $request->client['email'];
        $client->save();

        return $client;
    }
}
