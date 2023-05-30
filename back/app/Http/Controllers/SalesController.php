<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Detail;
use App\Models\Product;
use App\Models\Sales;
use App\Http\Requests\StoreSalesRequest;
use App\Http\Requests\UpdateSalesRequest;

class SalesController extends Controller{
    public function index(){ return Sales::all(); }
    public function betweenDates($fechaInicio, $fechaFin){
        $fechaInicio.=' 00:00:00';
        $fechaFin.=' 23:59:59';
        return Sales::whereBetween('fechaEmision', [$fechaInicio, $fechaFin])
            ->with('details')->orderBy('fechaEmision','desc')
            ->with('client')
            ->get();
    }
    public function store(StoreSalesRequest $request){

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
            $productSale->save();
        }
        $sales->concepto = substr($concepto,0,-1);
        $sales->save();

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
            $client = Client::find($request->client['id']);
            $client->nombreRazonSocial = strtoupper($request->client['nombreRazonSocial']);
            $client->codigoTipoDocumentoIdentidad = $request->client['codigoTipoDocumentoIdentidad'];
            $client->email = $request->client['email'];
            $client->save();
            return $client;
//            return "actualizado con complento";
        } else if (Client::where('numeroDocumento', $request->client['numeroDocumento'])->whereComplemento($complemento)->count()) {
            $client = Client::find($request->client['id']);
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
}
