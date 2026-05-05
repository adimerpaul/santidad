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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SalesController extends Controller
{
    public function __construct(private readonly FacturacionSiatService $facturacionService) {}

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
        error_log('cliente numeroDocumento: ' . $request->client['numeroDocumento']);

        if ($request->client['numeroDocumento'] !== '0') {
            if (!Cufd::where('fechaVigencia', '>', date('Y-m-d H:i:s'))->exists()) {
                return response()->json([
                    'message' => 'El cliente tiene que tener un CUFD registrado para poder realizar la venta',
                ], 400);
            }
        }

        // Verificar stock antes de abrir transacción
        foreach ($request->products as $product) {
            $productModel = Product::find($product['id']);
            if ($productModel->cantidad < $product['cantidadPedida']) {
                return response()->json([
                    'message' => 'No hay suficiente stock del producto ' . $productModel->nombre,
                ], 400);
            }
        }

        DB::beginTransaction();
        try {
            $client     = $this->insertUpdateClient($request);
            $agencia_id = $request->agencia_id;

            $montoBase  = array_reduce(
                $request->products,
                fn ($carry, $p) => $carry + $p['cantidadPedida'] * $p['precioVenta'],
                0
            );
            $montoTotal = $montoBase + $request->aporte - $request->descuento;

            $sale = new Sales();
            $sale->fill([
                'numeroFactura' => 0,
                'fechaEmision'  => date('Y-m-d H:i:s'),
                'montoTotal'    => $montoTotal,
                'usuario'       => $request->user()->name,
                'venta'         => 'R',
                'tipoVenta'     => 'Ingreso',
                'metodoPago'    => $request->metodoPago,
                'client_id'     => $client->id,
                'aporte'        => $request->aporte,
                'descuento'     => $request->descuento,
                'user_id'       => $request->user()->id,
                'agencia_id'    => $agencia_id,
            ]);
            $sale->save();

            $concepto           = '';
            $descuento_producto = 0;

            foreach ($request->products as $product) {
                $detail = new Detail();
                $detail->fill([
                    'cantidad'       => $product['cantidadPedida'],
                    'precioUnitario' => $product['precioVenta'],
                    'subTotal'       => $product['cantidadPedida'] * $product['precioVenta'],
                    'sale_id'        => $sale->id,
                    'descripcion'    => $product['nombre'],
                    'user_id'        => $request->user()->id,
                    'product_id'     => $product['id'],
                ]);
                $detail->save();

                $concepto .= $product['cantidadPedida'] . $product['nombre'] . ',';

                $productSale = Product::find($product['id']);
                $productSale->cantidad -= $product['cantidadPedida'];
                $this->ajustarStockSucursal($productSale, $agencia_id, -$product['cantidadPedida']);

                if ($productSale->porcentaje > 0) {
                    $descuento_producto += $product['cantidadPedida'] * $productSale->precio * $productSale->porcentaje / 100;
                }
                $productSale->save();

                foreach ($product['buys'] as $buy) {
                    if (isset($buy['cantidadAVender']) && $buy['cantidadAVender'] > 0) {
                        $buyModel                   = Buy::find($buy['id']);
                        $buyModel->cantidadVendida -= $buy['cantidadAVender'];
                        $buyModel->save();
                    }
                }
            }

            $sale->concepto           = rtrim($concepto, ',');
            $sale->descuento_producto = $descuento_producto;
            $sale->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }

        // SIAT y correo fuera de la transacción; un fallo no cancela la venta
        $online = $this->facturacionService->procesar($sale);
        $this->enviarCorreoFactura($sale, $online);

        return Sales::with(['details.product', 'client'])->find($sale->id);
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

            $montoTotal = 0;
            $concepto   = '';

            foreach ($request->details as $product) {
                $concepto   .= $product['cantidad'] . $product['descripcion'] . ',';
                $montoTotal += $product['cantidad'] * $product['precioUnitario'];

                $detail = new Detail();
                $detail->fill([
                    'cantidad'       => $product['cantidad'],
                    'precioUnitario' => $product['precioUnitario'],
                    'subTotal'       => round($product['cantidad'] * $product['precioUnitario'], 2),
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

            $sale->montoTotal = $montoTotal;
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

        $sale = new Sales();
        $sale->fill([
            'numeroFactura' => 0,
            'fechaEmision'  => date('Y-m-d H:i:s'),
            'montoTotal'    => $request->montoTotal,
            'usuario'       => $request->user()->name,
            'concepto'      => $request->concepto,
            'tipoVenta'     => 'Egreso',
            'metodoPago'    => $request->metodoPago,
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

        return Sales::with(['details', 'client'])->find($sale->id);
    }

    // ─────────────────────────── Reportes ───────────────────────────

    public function betweenDates($fechaInicio, $fechaFin, Request $request)
    {
        $fechaInicio .= ' 00:00:00';
        $fechaFin    .= ' 23:59:59';

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
        return Sales::whereBetween('fechaEmision', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->where('estado', '!=', 'ANULADO')
            ->with('user')
            ->get();
    }

    public function reportTotalIngreso($fechaInicio, $fechaFin)
    {
        return Sales::whereBetween('fechaEmision', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->where('estado', '!=', 'ANULADO')
            ->where('tipoVenta', 'Ingreso')
            ->with('user')
            ->get();
    }

    public function reportTotalEgreso($fechaInicio, $fechaFin)
    {
        return Sales::whereBetween('fechaEmision', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
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
        $products = DB::table('products as p')
            ->whereIn('p.id', $ids)
            ->select('p.id', 'p.nombre', 'p.imagen', 'p.precio', 'p.porcentaje', 'p.cantidad')
            ->get()
            ->map(function ($p) {
                if (!$p->imagen || !file_exists(public_path('/images/' . $p->imagen))) {
                    $p->imagen = 'productDefault.jpg';
                }
                return $p;
            })
            ->keyBy('id');

        return response()->json(
            $rows->map(function ($r) use ($products) {
                $p = $products[$r->product_id] ?? null;
                if (!$p) {
                    return null;
                }

                $precio       = (float) $p->precio;
                $precioNormal = null;
                if (!empty($p->porcentaje) && (int) $p->porcentaje > 0) {
                    $precioNormal = $precio;
                    $precio       = round($precio - ($precio * $p->porcentaje / 100), 2);
                }

                return [
                    'id'           => (int) $p->id,
                    'nombre'       => $p->nombre,
                    'imagen'       => $p->imagen ?: 'productDefault.jpg',
                    'precio'       => number_format($precio, 2, '.', ''),
                    'precioNormal' => $precioNormal,
                    'porcentaje'   => (int) ($p->porcentaje ?? 0),
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
            $client->nombreRazonSocial           = strtoupper($request->client['nombreRazonSocial']);
            $client->codigoTipoDocumentoIdentidad = $request->client['codigoTipoDocumentoIdentidad'];
            $client->email                       = $request->client['email'];
            $client->save();
            return $client;
        }

        $client                              = new Client();
        $client->nombreRazonSocial           = strtoupper($request->client['nombreRazonSocial']);
        $client->codigoTipoDocumentoIdentidad = $request->client['codigoTipoDocumentoIdentidad'];
        $client->numeroDocumento             = $request->client['numeroDocumento'];
        $client->complemento                 = strtoupper($complemento);
        $client->email                       = $request->client['email'];
        $client->save();

        return $client;
    }
}
