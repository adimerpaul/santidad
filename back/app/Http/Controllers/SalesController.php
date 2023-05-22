<?php

namespace App\Http\Controllers;

use App\Models\Detail;
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
    public function store(StoreSalesRequest $request){ return Sales::create($request->all()); }
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
}
