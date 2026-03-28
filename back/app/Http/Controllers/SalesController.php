<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Cufd;
use App\Models\Cuis;
use App\Models\Detail;
use App\Models\Product;
use App\Models\Sales;
use App\Http\Requests\StoreSalesRequest;
use App\Http\Requests\UpdateSalesRequest;
use App\Services\SiatCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;


class SalesController extends Controller{
    public function __construct(private readonly SiatCodeService $siatCodeService)
    {
    }
    public function salesAnular($id, Request $request){
        $sale = Sales::find($id);
        $sale->estado = 'ANULADO';
        $sale->save();
        $details = Detail::whereSaleId($id)->get();
        if ($sale->tipoVenta == 'Ingreso'){
            foreach ($details as $detail){
                $product = Product::find($detail->product_id);
                $product->cantidad = $product->cantidad + $detail->cantidad;
                $numeroSucursal = $sale->agencia_id;
                if ($numeroSucursal == 1){
                    $product->cantidadSucursal1 = $product->cantidadSucursal1 + $detail->cantidad;
                }else if ($numeroSucursal == 2){
                    $product->cantidadSucursal2 = $product->cantidadSucursal2 + $detail->cantidad;
                }else if ($numeroSucursal == 3){
                    $product->cantidadSucursal3 = $product->cantidadSucursal3 + $detail->cantidad;
                }else if ($numeroSucursal == 4){
                    $product->cantidadSucursal4 = $product->cantidadSucursal4 + $detail->cantidad;
                }else if ($numeroSucursal == 5){
                    $product->cantidadSucursal5 = $product->cantidadSucursal5 + $detail->cantidad;
                }else if ($numeroSucursal == 6){
                    $product->cantidadSucursal6 = $product->cantidadSucursal6 + $detail->cantidad;
                }else if ($numeroSucursal == 7){
                    $product->cantidadSucursal7 = $product->cantidadSucursal7 + $detail->cantidad;
                }else if ($numeroSucursal == 8){
                    $product->cantidadSucursal8 = $product->cantidadSucursal8 + $detail->cantidad;
                }else if ($numeroSucursal == 9){
                    $product->cantidadSucursal9 = $product->cantidadSucursal9 + $detail->cantidad;
                }else if ($numeroSucursal == 10){
                    $product->cantidadSucursal10 = $product->cantidadSucursal10 + $detail->cantidad;
                }
                $product->save();
            }
        }
//        return $sales;
    }
    public function index(){ return Sales::all(); }
    public function betweenDates($fechaInicio, $fechaFin,Request $request){
        $agencia_id = $request->agencia;
        $user_id = $request->user;
        $fechaInicio.=' 00:00:00';
        $fechaFin.=' 23:59:59';
        $sales= Sales::whereBetween('fechaEmision', [$fechaInicio, $fechaFin])
            ->with('details')->orderBy('fechaEmision','desc')
            ->with('client')
            ->with('user')
            ->with('agencia');

        if ($agencia_id != 0){
            $sales = $sales->where('agencia_id',$agencia_id);
        }
        if ($user_id != 0){
            $sales = $sales->where('user_id',$user_id);
        }
        return $sales->get();
    }
    public function store(StoreSalesRequest $request){
        DB::beginTransaction();
        $agencia_id = $request->agencia_id;
//        if ($request->client['numeroDocumento'] == 0){
//            DB::rollBack();
//            return response()->json(['message' => 'El número de documento no puede ser 0'], 400);
//        }
//        si es diferente de cero cuf preguntar el por cufd
        error_log('cliente numeroDocumento: '.$request->client['numeroDocumento']);
        if ($request->client['numeroDocumento'] != '0'){
            $ultimoCufd = Cufd::where('fechaVigencia', '>', date('Y-m-d H:i:s'))->first();
            if (!$ultimoCufd) {
                DB::rollBack();
                return response()->json(['message' => 'El cliente tiene que tener un CUFD registrado para poder realizar la venta'], 400);
            }
        }
        $client=$this->insertUpdateClient($request);

        foreach ($request->products as $product){
            $productModel = Product::find($product['id']);
            if ($productModel->cantidad < $product['cantidadPedida']){
                DB::rollBack();
                return response()->json(['message' => 'No hay suficiente stock del producto '.$productModel->nombre], 400);
            }
        }


        $montoTotalDetalles = 0;
        foreach ($request->products as $product) {
            $montoTotalDetalles += $product['cantidadPedida'] * $product['precioVenta'];
        }
        $montoTotal = $montoTotalDetalles + $request->aporte - $request->descuento;
        $sales = new Sales();
        $sales->numeroFactura = 0;
        $sales->fechaEmision = date('Y-m-d H:i:s');
        $sales->montoTotal = $montoTotal;
        $sales->usuario = $request->user()->name;
        $sales->venta = 'R';
        $sales->tipoVenta = 'Ingreso';
        $sales->metodoPago = $request->metodoPago;
        $sales->client_id = $client->id;
        $sales->aporte = $request->aporte;
        $sales->descuento = $request->descuento;
        $sales->user_id = $request->user()->id;
        $sales->agencia_id = $agencia_id;
        $sales->save();
        $concepto = "";
        $descuento_producto = 0;
        foreach ($request->products as $product){
            $detail = new Detail();
            $detail->cantidad = $product['cantidadPedida'];
            $detail->precioUnitario = $product['precioVenta'];
            $detail->subTotal =  $product['cantidadPedida'] * $product['precioVenta'];
            $detail->sale_id = $sales->id;
            $detail->descripcion = $product['nombre'];
            $detail->user_id = $request->user()->id;
            $detail->product_id = $product['id'];
            $detail->save();
            $concepto .= $product['cantidadPedida'].$product['nombre'].',';

            $productSale= Product::find($product['id']);
            $productSale->cantidad = $productSale->cantidad - $product['cantidadPedida'];
            $numeroSucursal = $agencia_id;
            if ($numeroSucursal == 1){
                $productSale->cantidadSucursal1 = $productSale->cantidadSucursal1 - $product['cantidadPedida'];
            }else if ($numeroSucursal == 2){
                $productSale->cantidadSucursal2 = $productSale->cantidadSucursal2 - $product['cantidadPedida'];
            }else if ($numeroSucursal == 3){
                $productSale->cantidadSucursal3 = $productSale->cantidadSucursal3 - $product['cantidadPedida'];
            }else if ($numeroSucursal == 4){
                $productSale->cantidadSucursal4 = $productSale->cantidadSucursal4 - $product['cantidadPedida'];
            }else if ($numeroSucursal == 5){
                $productSale->cantidadSucursal5 = $productSale->cantidadSucursal5 - $product['cantidadPedida'];
            }else if ($numeroSucursal == 6){
                $productSale->cantidadSucursal6 = $productSale->cantidadSucursal6 - $product['cantidadPedida'];
            }else if ($numeroSucursal == 7){
                $productSale->cantidadSucursal7 = $productSale->cantidadSucursal7 - $product['cantidadPedida'];
            }else if ($numeroSucursal == 8){
                $productSale->cantidadSucursal8 = $productSale->cantidadSucursal8 - $product['cantidadPedida'];
            }else if ($numeroSucursal == 9){
                $productSale->cantidadSucursal9 = $productSale->cantidadSucursal9 - $product['cantidadPedida'];
            }else if ($numeroSucursal == 10){
                $productSale->cantidadSucursal10 = $productSale->cantidadSucursal10 - $product['cantidadPedida'];
            }
            if( $productSale->porcentaje > 0){
                $montoAhorrro = $product['cantidadPedida'] * $productSale->precio * $productSale->porcentaje / 100;
                $descuento_producto += $montoAhorrro;
            }
            $productSale->save();

            $buys = $product['buys'];
            foreach ($buys as $buy){
//                $cantidadAVender = $buy['cantidadAVender'];
//                if ($cantidadAVender != "" && $cantidadAVender > 0){
//                    $buyModel = \App\Models\Buy::find($buy['id']);
//                    error_log(json_encode($buyModel));
//                    $buyModel->cantidadVendida = $buyModel->cantidadVendida - $cantidadAVender;
//                    $buyModel->save();
//                }
                if (isset($buy['cantidadAVender']) && $buy['cantidadAVender'] != "" && $buy['cantidadAVender'] > 0){
                    $buyModel = \App\Models\Buy::find($buy['id']);
                    $buyModel->cantidadVendida = $buyModel->cantidadVendida - $buy['cantidadAVender'];
                    $buyModel->save();
                }
            }
        }
        $sales->concepto = substr($concepto,0,-1);
        $sales->descuento_producto = $descuento_producto;
        $sales->save();
        $this->recepcionFacturaSiat($sales);
        DB::commit();
        return Sales::with(['details.product','client'])->find($sales->id);

    }
    function recepcionFactura(Sales $sales){
        $client = $sales->client;
        if ($client->numeroDocumento == '0'){
            return null;
        }
        $cuiUltimo = Cui::where('fechaVigencia', '>', date('Y-m-d H:i:s'))->first();
        $cufdUltimo = Cufd::where('fechaVigencia', '>', date('Y-m-d H:i:s'))->first();
//            "codigoAmbiente"=>$codigoAmbiente,
//            "codigoDocumentoSector"=>$codigoDocumentoSector,
//            "codigoEmision"=>$codigoEmision,
//            "codigoModalidad"=>$codigoModalidad,
//            "codigoPuntoVenta"=>$codigoPuntoVenta,
//            "codigoSistema"=>$codigoSistema,
//            "codigoSucursal"=>$codigoSucursal,
//            "cufd"=>$cufd,
//            "cuis"=>$cuis,
//            "nit"=>$nit,
//            "tipoFacturaDocumento"=>$tipoFacturaDocumento,
//            "archivo"=>$archivo,
//            "fechaEnvio"=>$fechaEnvio,
//            "hashArchivo"=>$hashArchivo,
        $payload = [
            'codigoAmbiente' => (int) config('siat.codigo_ambiente'),
            'codigoDocumentoSector' => 1, // factura
            'codigoEmision' => 1, // emisión normal
            'codigoModalidad' => (int) config('siat.codigo_modalidad'),
            'codigoPuntoVenta' => 0,
            'codigoSistema' => (string) config('siat.codigo_sistema'),
            'codigoSucursal' => 0,
            'cufd' => $cufdUltimo ? $cufdUltimo->codigo : null,
            'cuis' => $cuiUltimo ? $cuiUltimo->codigo : null,
            'nit' => (int) config('siat.nit'),
            'tipoFacturaDocumento' => 1, // factura electrónica
        ];
    }
    public function salesGasto(StoreSalesRequest $request){
        if ($request->concepto == ''){
            $numeroGasto = Sales::where('tipoVenta','Egreso')->count();
            $request->concepto = 'Gasto '.($numeroGasto+1);
        }
        $sales = new Sales();
        $sales->numeroFactura = 0;
        $sales->fechaEmision = date('Y-m-d H:i:s');
        $sales->montoTotal = $request->montoTotal;
        $sales->usuario = $request->user()->name;
        $sales->concepto = $request->concepto;
        $sales->tipoVenta = 'Egreso';
        $sales->metodoPago = $request->metodoPago;
        $sales->client_id = $request->client_id==0?null:$request->client_id;
        $sales->user_id = $request->user()->id;
        $sales->save();

        $detail = new Detail();
        $detail->cantidad = 1;
        $detail->precioUnitario = $request->montoTotal;
        $detail->subTotal = $request->montoTotal;
        $detail->sale_id = $sales->id;
        $detail->descripcion = $request->concepto;
        $detail->user_id = $request->user()->id;
        $detail->save();
        return Sales::with(['details','client'])->find($sales->id);
    }
    public function show(Sales $sales){ return $sales; }
    public function update(UpdateSalesRequest $request, Sales $sale){
        try {
         DB::beginTransaction();
            $numeroSucursal = $sale->agencia_id;
            //restaurar stock
            foreach ($sale->details as $detail){
                $product = Product::find($detail->product_id);
                $product->cantidad = $product->cantidad + $detail->cantidad;
                if ($numeroSucursal == 1){
                    $product->cantidadSucursal1 = $product->cantidadSucursal1 + $detail->cantidad;
                }else if ($numeroSucursal == 2){
                    $product->cantidadSucursal2 = $product->cantidadSucursal2 + $detail->cantidad;
                }else if ($numeroSucursal == 3){
                    $product->cantidadSucursal3 = $product->cantidadSucursal3 + $detail->cantidad;
                }else if ($numeroSucursal == 4){
                    $product->cantidadSucursal4 = $product->cantidadSucursal4 + $detail->cantidad;
                }else if ($numeroSucursal == 5){
                    $product->cantidadSucursal5 = $product->cantidadSucursal5 + $detail->cantidad;
                }else if ($numeroSucursal == 6){
                    $product->cantidadSucursal6 = $product->cantidadSucursal6 + $detail->cantidad;
                }else if ($numeroSucursal == 7){
                    $product->cantidadSucursal7 = $product->cantidadSucursal7 + $detail->cantidad;
                }else if ($numeroSucursal == 8){
                    $product->cantidadSucursal8 = $product->cantidadSucursal8 + $detail->cantidad;
                }else if ($numeroSucursal == 9){
                    $product->cantidadSucursal9 = $product->cantidadSucursal9 + $detail->cantidad;
                }else if ($numeroSucursal == 10){
                    $product->cantidadSucursal10 = $product->cantidadSucursal10 + $detail->cantidad;
                }
                $product->save();
            }
            //eliminamos detalles
            Detail::whereSaleId($sale->id)->delete();
            //actualizamos venta
            $montoTotal= 0;
            $concepto = "";
            foreach ($request->details as $product) {
                $concepto .= $product['cantidad'].$product['descripcion'].',';
                $montoTotal += ($product['cantidad'] * $product['precioUnitario']);
                $detail = new Detail();
                $detail->cantidad = $product['cantidad'];
                $detail->precioUnitario = $product['precioUnitario'];
                $detail->subTotal = round($product['cantidad'] * $product['precioUnitario'], 2);
                $detail->sale_id = $sale->id;
                $detail->descripcion = $product['descripcion'];
                $detail->user_id = $request->user()->id;
                $detail->product_id = $product['product_id'];
                $detail->save();
                $productSale = Product::find($product['product_id']);
                $productSale->cantidad = $productSale->cantidad - $product['cantidad'];
                if ($numeroSucursal == 1) {
                    $productSale->cantidadSucursal1 = $productSale->cantidadSucursal1 - $product['cantidad'];
                } else if ($numeroSucursal == 2) {
                    $productSale->cantidadSucursal2 = $productSale->cantidadSucursal2 - $product['cantidad'];
                } else if ($numeroSucursal == 3) {
                    $productSale->cantidadSucursal3 = $productSale->cantidadSucursal3 - $product['cantidad'];
                } else if ($numeroSucursal == 4) {
                    $productSale->cantidadSucursal4 = $productSale->cantidadSucursal4 - $product['cantidad'];
                } else if ($numeroSucursal == 5) {
                    $productSale->cantidadSucursal5 = $productSale->cantidadSucursal5 - $product['cantidad'];
                } else if ($numeroSucursal == 6) {
                    $productSale->cantidadSucursal6 = $productSale->cantidadSucursal6 - $product['cantidad'];
                } else if ($numeroSucursal == 7) {
                    $productSale->cantidadSucursal7 = $productSale->cantidadSucursal7 - $product['cantidad'];
                } else if ($numeroSucursal == 8) {
                    $productSale->cantidadSucursal8 = $productSale->cantidadSucursal8 - $product['cantidad'];
                } else if ($numeroSucursal == 9) {
                    $productSale->cantidadSucursal9 = $productSale->cantidadSucursal9 - $product['cantidad'];
                } else if ($numeroSucursal == 10) {
                    $productSale->cantidadSucursal10 = $productSale->cantidadSucursal10 - $product['cantidad'];
                }
                $productSale->save();
            }
            $sale->montoTotal = $montoTotal;
            $sale->concepto = substr($concepto,0,-1);
            $sale->modificado = 'SI';
            $sale->save();
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public function destroy(Sales $sales){ return $sales->delete(); }

    /**
     * @param StoreSalesRequest $request
     * @return void
     */
    public function insertUpdateClient(StoreSalesRequest $request)
    {
        if ($request->client['complemento'] == null) {
            $complemento = "";
        } else {
            $complemento = $request->client['complemento'];
        }
        if ($complemento != "" && Client::whereComplemento($complemento)->where('numeroDocumento', $request->client['numeroDocumento'])->count() == 1) {
            $client = Client::whereComplemento($complemento)->where('numeroDocumento', $request->client['numeroDocumento'])->first();
            $client->nombreRazonSocial = strtoupper($request->client['nombreRazonSocial']);
            $client->codigoTipoDocumentoIdentidad = $request->client['codigoTipoDocumentoIdentidad'];
            $client->email = $request->client['email'];
            $client->save();
            return $client;
//            return "actualizado con complento";
        } else if (Client::where('numeroDocumento', $request->client['numeroDocumento'])->whereComplemento($complemento)->count()) {
            $client = Client::where('numeroDocumento', $request->client['numeroDocumento'])->whereComplemento($complemento)->first();
            $client->nombreRazonSocial = strtoupper($request->client['nombreRazonSocial']);
            $client->codigoTipoDocumentoIdentidad = $request->client['codigoTipoDocumentoIdentidad'];
            $client->email = $request->client['email'];
            $client->save();
            return $client;
//            return "actualizado";
        } else {
            $client = new Client();
            $client->nombreRazonSocial = strtoupper($request->client['nombreRazonSocial']);
            $client->codigoTipoDocumentoIdentidad = $request->client['codigoTipoDocumentoIdentidad'];
            $client->numeroDocumento = $request->client['numeroDocumento'];
            $client->complemento = strtoupper($request->client['complemento']);
            $client->email = $request->client['email'];
            $client->save();
            return $client;
//            return "nuevo";
        }
    }
    public function reportTotal($fechaInicio, $fechaFin){
        $fechaInicio.=' 00:00:00';
        $fechaFin.=' 23:59:59';
        $sales = Sales::whereBetween('fechaEmision', [$fechaInicio, $fechaFin])
            ->where('estado','!=','ANULADO')
            ->with('user')
            ->get();
        return $sales;
    }
    public function reportTotalIngreso($fechaInicio, $fechaFin){
        $fechaInicio.=' 00:00:00';
        $fechaFin.=' 23:59:59';
        $sales = Sales::whereBetween('fechaEmision', [$fechaInicio, $fechaFin])
            ->where('estado','!=','ANULADO')
            ->where('tipoVenta','Ingreso')
            ->with('user')
            ->get();
        return $sales;
    }
    public function reportTotalEgreso($fechaInicio, $fechaFin){
        $fechaInicio.=' 00:00:00';
        $fechaFin.=' 23:59:59';
        $sales = Sales::whereBetween('fechaEmision', [$fechaInicio, $fechaFin])
            ->where('estado','!=','ANULADO')
            ->where('tipoVenta','Egreso')
            ->with('user')
            ->get();
        return $sales;
    }
public function topSellers(Request $request)
{
    // ?days=1 (hoy) | 7 | 30 ...
    $days = (int) $request->get('days', 1);
    $days = max($days, 1);

    // ?agencia_id=2 (opcional)
    $agenciaId = $request->get('agencia_id');

    // Rango de fechas basado en sales.fechaEmision
    $start = Carbon::now()->subDays($days - 1)->startOfDay();
    $end   = Carbon::now()->endOfDay();

    // 1) Cantidades por producto (solo ventas válidas)
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

    // 2) Datos de producto
    $ids = $rows->pluck('product_id')->all();

    // Traemos productos y NORMALIZAMOS imagen como en ProductController
    $products = DB::table('products as p')
        ->whereIn('p.id', $ids)
        ->select('p.id','p.nombre','p.imagen','p.precio','p.porcentaje','p.cantidad')
        ->get()
        ->map(function ($p) {
            $nombre = $p->imagen;

            // Si viene vacío o el archivo no existe, forzar el default EXACTO que usas
            if (!$nombre || !file_exists(public_path('/images/' . $nombre))) {
                $p->imagen = 'productDefault.jpg';
            }

            return $p;
        })
        ->keyBy('id');

    // 3) Armar respuesta respetando el orden de ventas
    $out = $rows->map(function ($r) use ($products) {
        $p = $products[$r->product_id] ?? null;
        if (!$p) return null;

        // Precios con % de descuento
        $precio = (float) $p->precio;
        $precioNormal = null;
        if (!empty($p->porcentaje) && (int)$p->porcentaje > 0) {
            $precioNormal = $precio;
            $precio = round($precio - ($precio * $p->porcentaje / 100), 2);
        }

        return [
            'id'           => (int)$p->id,
            'nombre'       => $p->nombre,
            'imagen'       => $p->imagen ?: 'productDefault.jpg', // ya normalizado arriba
            'precio'       => number_format($precio, 2, '.', ''),
            'precioNormal' => $precioNormal,
            'porcentaje'   => (int)($p->porcentaje ?? 0),
            'cantidad'     => (int)($p->cantidad ?? 0),   // stock actual
            'vendido'      => (int)$r->cantidad_total,    // vendidos en el rango
        ];
    })->filter()->values();

    return response()->json($out);
}

    private function recepcionFacturaSiat(Sales $sales)
    {
        $sales->loadMissing(['details.product', 'client', 'user', 'agencia']);
        $client = $sales->client;

        if (!$client || $client->numeroDocumento == '0') {
            return null;
        }

        $cuiUltimo = Cuis::where('fechaVigencia', '>', date('Y-m-d H:i:s'))
            ->where('codigoSucursal', 0)
            ->where('codigoPuntoVenta', 0)
            ->latest('id')
            ->first();
        $cufdUltimo = Cufd::where('fechaVigencia', '>', date('Y-m-d H:i:s'))
            ->where('codigoSucursal', 0)
            ->where('codigoPuntoVenta', 0)
            ->latest('id')
            ->first();

        if (!$cuiUltimo || !$cufdUltimo) {
            throw new \RuntimeException('No existe CUIS/CUFD vigente para facturar en SIAT');
        }

        $codigoSucursal = 0;
        $codigoPuntoVenta = 0;
        $fechaEmision = Carbon::now('America/La_Paz');
        $mili = str_pad((string) ((int) floor(((int) $fechaEmision->format('u')) / 1000)), 3, '0', STR_PAD_LEFT);
        $fechaSiat = $fechaEmision->format('Y-m-d\TH:i:s') . '.' . $mili;
        $numeroFactura = $sales->numeroFactura && $sales->numeroFactura > 0
            ? (int) $sales->numeroFactura
            : ((int) Sales::max('numeroFactura') + 1);
        $leyenda = $this->leyendaSiat();
        $cuf = $this->generarCuf(
            (string) config('siat.nit'),
            $fechaEmision->format('YmdHis') . $mili,
            (string) $codigoSucursal,
            (string) config('siat.codigo_modalidad'),
            '1',
            '1',
            '1',
            (string) $numeroFactura,
            (string) $codigoPuntoVenta,
            (string) $cufdUltimo->codigoControl
        );

        $sales->numeroFactura = $numeroFactura;
        $sales->venta = 'F';
        $sales->cuf = $cuf;
        $sales->cufd = $cufdUltimo->codigo;
        $sales->cui = $cuiUltimo->codigo;
        $sales->codigoSucursal = $codigoSucursal;
        $sales->codigoPuntoVenta = $codigoPuntoVenta;
        $sales->codigoDocumentoSector = 1;
        $sales->fechaEmision = $fechaEmision->format('Y-m-d H:i:s');
        $sales->leyenda = $leyenda;
        $sales->cufd_id = $cufdUltimo->id;
        $sales->save();

        $xml = $this->buildSiatXml(
            $sales,
            $numeroFactura,
            $cuf,
            (string) $cufdUltimo->codigo,
            $codigoSucursal,
            $codigoPuntoVenta,
            $fechaSiat,
            $leyenda
        );
        error_log('XML SIAT: ' . $xml);
        $this->validateSiatXml($xml);

        $gzContent = gzencode($xml, 9);
        $hashArchivo = hash('sha256', $gzContent);
        $this->storeSiatXml($sales->id, $xml, $gzContent);

        $payload = [
            'codigoAmbiente' => (int) config('siat.codigo_ambiente'),
            'codigoDocumentoSector' => 1,
            'codigoEmision' => 1,
            'codigoModalidad' => (int) config('siat.codigo_modalidad'),
            'codigoPuntoVenta' => $codigoPuntoVenta,
            'codigoSistema' => (string) config('siat.codigo_sistema'),
            'codigoSucursal' => $codigoSucursal,
            'cufd' => $cufdUltimo->codigo,
            'cuis' => $cuiUltimo->codigo,
            'nit' => (int) config('siat.nit'),
            'tipoFacturaDocumento' => 1,
            'archivo' => $gzContent,
            'fechaEnvio' => $fechaSiat,
            'hashArchivo' => $hashArchivo,
        ];

        $result = $this->siatCodeService->recepcionFactura($payload);
        error_log('Respuesta SIAT: ' . json_encode($result));
        $transaccion = data_get($result, 'RespuestaServicioFacturacion.transaccion')
            ?? data_get($result, 'transaccion');

        if (!$transaccion) {
            throw new \RuntimeException(
                data_get($result, 'RespuestaServicioFacturacion.mensajesList.0.descripcion')
                ?? data_get($result, 'mensajesList.0.descripcion')
                ?? 'SIAT rechazo la recepcion de la factura'
            );
        }

        $sales->codigoRecepcion = data_get($result, 'RespuestaServicioFacturacion.codigoRecepcion')
            ?? data_get($result, 'codigoRecepcion');
        $sales->siatEnviado = true;
//        $sales->qr = 'SI';
        $sales->save();

        return $result;
    }

    private function leyendaSiat(): string
    {
        $leyendas = [
            'Ley N° 453: Puedes acceder a la reclamacion cuando tus derechos han sido vulnerados.',
            'Ley N° 453: El proveedor debe brindar atencion sin discriminacion, con respeto, calidez y cordialidad a los usuarios y consumidores.',
        ];

        return $leyendas[array_rand($leyendas)];
    }

    private function buildSiatXml(
        Sales $sale,
        int $numeroFactura,
        string $cuf,
        string $cufd,
        int $codigoSucursal,
        int $codigoPuntoVenta,
        string $fechaSiat,
        string $leyenda
    ): string {
        $client = $sale->client ?: new Client([
            'nombreRazonSocial' => 'SIN NOMBRE',
            'numeroDocumento' => '0',
            'codigoTipoDocumentoIdentidad' => 1,
            'complemento' => null,
        ]);

        $detalles = '';
        foreach ($sale->details as $detail) {
            $actividadEconomica = $detail->actividadEconomica ?: '4772100';
            $codigoProductoSin = $detail->codigoProductoSin ?: '1003655';
            $codigoProducto = $detail->product_id ?: 0;
            $descripcion = $this->xmlValue($detail->descripcion ?: 'PRODUCTO');
            $cantidad = number_format((float) $detail->cantidad, 2, '.', '');
            $precioUnitario = number_format((float) $detail->precioUnitario, 2, '.', '');
            $subTotal = number_format((float) $detail->subTotal, 2, '.', '');

            $detalles .= <<<XML
    <detalle>
        <actividadEconomica>{$actividadEconomica}</actividadEconomica>
        <codigoProductoSin>{$codigoProductoSin}</codigoProductoSin>
        <codigoProducto>{$codigoProducto}</codigoProducto>
        <descripcion>{$descripcion}</descripcion>
        <cantidad>{$cantidad}</cantidad>
        <unidadMedida>1</unidadMedida>
        <precioUnitario>{$precioUnitario}</precioUnitario>
        <montoDescuento>0</montoDescuento>
        <subTotal>{$subTotal}</subTotal>
        <numeroSerie xsi:nil="true"/>
        <numeroImei xsi:nil="true"/>
    </detalle>

XML;
        }

        $razon = $this->xmlValue((string) env('RAZON', 'Santidad Divina'));
        $nit = $this->xmlValue((string) config('siat.nit'));
        $municipio = $this->xmlValue((string) env('MUNICIPIO', 'Oruro'));
        $telefono = $this->xmlValue((string) env('TELEFONO', ''));
        error_log('telefono SIAT: ' . $telefono);
        $direccion = $this->xmlValue((string) env('DIRECCION', ''));
        $nombreRazonSocial = $this->xmlValue((string) ($client->nombreRazonSocial ?: 'SIN NOMBRE'));
        $numeroDocumento = $this->xmlValue((string) ($client->numeroDocumento ?: '0'));
        $codigoCliente = $client->id ?: 0;
        $codigoMetodoPago = $this->codigoMetodoPago($sale->metodoPago);
        $montoTotal = number_format((float) $sale->montoTotal, 2, '.', '');
        $descuentoAdicional = $sale->descuento && (float) $sale->descuento > 0
            ? '<descuentoAdicional>' . number_format((float) $sale->descuento, 2, '.', '') . '</descuentoAdicional>'
            : '<descuentoAdicional xsi:nil="true"/>';
        $complemento = $client->complemento
            ? '<complemento>' . $this->xmlValue((string) $client->complemento) . '</complemento>'
            : '<complemento xsi:nil="true"/>';
        $usuario = $this->xmlValue((string) ($sale->user?->name ?: $sale->usuario ?: 'admin'));
        $leyendaXml = $this->xmlValue(mb_substr($leyenda, 0, 200));

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<facturaComputarizadaCompraVenta xsi:noNamespaceSchemaLocation="facturaComputarizadaCompraVenta.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <cabecera>
        <nitEmisor>{$nit}</nitEmisor>
        <razonSocialEmisor>{$razon}</razonSocialEmisor>
        <municipio>{$municipio}</municipio>
        <telefono>{$telefono}</telefono>
        <numeroFactura>{$numeroFactura}</numeroFactura>
        <cuf>{$cuf}</cuf>
        <cufd>{$cufd}</cufd>
        <codigoSucursal>{$codigoSucursal}</codigoSucursal>
        <direccion>{$direccion}</direccion>
        <codigoPuntoVenta>{$codigoPuntoVenta}</codigoPuntoVenta>
        <fechaEmision>{$fechaSiat}</fechaEmision>
        <nombreRazonSocial>{$nombreRazonSocial}</nombreRazonSocial>
        <codigoTipoDocumentoIdentidad>{$client->codigoTipoDocumentoIdentidad}</codigoTipoDocumentoIdentidad>
        <numeroDocumento>{$numeroDocumento}</numeroDocumento>
        {$complemento}
        <codigoCliente>{$codigoCliente}</codigoCliente>
        <codigoMetodoPago>{$codigoMetodoPago}</codigoMetodoPago>
        <numeroTarjeta xsi:nil="true"/>
        <montoTotal>{$montoTotal}</montoTotal>
        <montoTotalSujetoIva>{$montoTotal}</montoTotalSujetoIva>
        <codigoMoneda>1</codigoMoneda>
        <tipoCambio>1</tipoCambio>
        <montoTotalMoneda>{$montoTotal}</montoTotalMoneda>
        <montoGiftCard xsi:nil="true"/>
        {$descuentoAdicional}
        <codigoExcepcion xsi:nil="true"/>
        <cafc xsi:nil="true"/>
        <leyenda>{$leyendaXml}</leyenda>
        <usuario>{$usuario}</usuario>
        <codigoDocumentoSector>1</codigoDocumentoSector>
    </cabecera>
{$detalles}</facturaComputarizadaCompraVenta>
XML;
    }

    private function validateSiatXml(string $xml): void
    {
        $schemaPath = realpath(base_path('../siat/facturaComputarizadaCompraVenta.xsd'));
        if (!$schemaPath || !is_file($schemaPath)) {
            throw new \RuntimeException('No se encontro el XSD de SIAT para validar la factura');
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        if (!$document->loadXML($xml)) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors(false);
            $message = $errors ? trim($errors[0]->message) : 'No se pudo cargar el XML';
            throw new \RuntimeException('XML SIAT invalido: ' . $message);
        }

        $valid = $document->schemaValidate($schemaPath);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        if ($valid) {
            return;
        }

        $messages = array_map(
            static fn ($error) => trim($error->message) . ' (linea ' . $error->line . ')',
            $errors
        );

        throw new \RuntimeException(
            'XML SIAT invalido: ' . ($messages ? implode(' | ', $messages) : 'schemaValidate devolvio invalid')
        );
    }

    private function storeSiatXml(int $saleId, string $xml, string $gzContent): void
    {
        try {
            Storage::disk('local')->put("siat/sales/{$saleId}.xml", $xml);
            Storage::disk('local')->put("siat/sales/{$saleId}.xml.gz", $gzContent);
        } catch (\Throwable $e) {
        }
    }

    private function codigoMetodoPago(?string $metodoPago): int
    {
        return match ($metodoPago) {
            'Tarjeta' => 2,
            'Transferencia' => 3,
            'Qr', 'QR' => 16,
            default => 1,
        };
    }

    private function xmlValue(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function generarCuf(
        string $nit,
        string $fechaHora,
        string $sucursal,
        string $modalidad,
        string $tipoEmision,
        string $codigoDocumentoFiscal,
        string $tipoDocumentoSector,
        string $numeroFactura,
        string $puntoVenta,
        string $codigoControl
    ): string {
        $cadena = '';
        $cadena .= str_pad($nit, 13, '0', STR_PAD_LEFT);
        $cadena .= $fechaHora;
        $cadena .= str_pad($sucursal, 4, '0', STR_PAD_LEFT);
        $cadena .= $modalidad;
        $cadena .= $tipoEmision;
        $cadena .= $codigoDocumentoFiscal;
        $cadena .= str_pad($tipoDocumentoSector, 2, '0', STR_PAD_LEFT);
        $cadena .= str_pad($numeroFactura, 10, '0', STR_PAD_LEFT);
        $cadena .= str_pad($puntoVenta, 4, '0', STR_PAD_LEFT);
        $cadena .= $this->calculaDigitoMod11($cadena, 1, 9, false);

        return $this->base16($cadena) . $codigoControl;
    }

    private function calculaDigitoMod11(string $dado, int $numDig, int $limMult, bool $x10): string
    {
        if (!$x10) {
            $numDig = 1;
        }

        for ($n = 1; $n <= $numDig; $n++) {
            $soma = 0;
            $mult = 2;
            for ($i = strlen($dado) - 1; $i >= 0; $i--) {
                $soma += ($mult * substr($dado, $i, 1));
                if (++$mult > $limMult) {
                    $mult = 2;
                }
            }

            $dig = $x10 ? (($soma * 10) % 11) % 10 : $soma % 11;

            if ($dig === 10) {
                $dado .= '1';
            } elseif ($dig === 11) {
                $dado .= '0';
            } else {
                $dado .= $dig;
            }
        }

        return substr($dado, strlen($dado) - $numDig, $numDig);
    }

    private function base16(string $number): string
    {
        $hexvalues = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F'];
        $hexval = '';

        while ($number !== '0') {
            $hexval = $hexvalues[(int) bcmod($number, '16')] . $hexval;
            $number = bcdiv($number, '16', 0);
        }

        return $hexval;
    }

}
