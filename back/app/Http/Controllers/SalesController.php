<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Detail;
use App\Models\Product;
use App\Models\Sales;
use App\Http\Requests\StoreSalesRequest;
use App\Http\Requests\UpdateSalesRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller{
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
        $client=$this->insertUpdateClient($request);
        $sales = new Sales();
        $sales->numeroFactura = 0;
        $sales->fechaEmision = date('Y-m-d H:i:s');
        $sales->montoTotal = $request->montoTotal;
        $sales->usuario = $request->user()->name;
        $sales->venta = 'R';
        $sales->tipoVenta = 'Ingreso';
        $sales->metodoPago = $request->metodoPago;
        $sales->client_id = $client->id;
        $sales->aporte = $request->aporte;
        $sales->user_id = $request->user()->id;
        $sales->agencia_id = $agencia_id;
        $sales->save();
        $concepto = "";
        foreach ($request->products as $product){
            $detail = new Detail();
            $detail->cantidad = $product['cantidadPedida'];
            $detail->precioUnitario = $product['precioVenta'];
            $detail->subTotal = $product['subTotal'];
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
            $productSale->save();
        }
        $sales->concepto = substr($concepto,0,-1);
        $sales->save();
        DB::commit();
        return Sales::with(['details','client'])->find($sales->id);

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
    public function update(UpdateSalesRequest $request, Sales $sales){ return $sales->update($request->all()); }
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
}
