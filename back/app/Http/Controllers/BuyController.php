<?php

namespace App\Http\Controllers;

use App\Models\Buy;
use App\Http\Requests\StoreBuyRequest;
use App\Http\Requests\UpdateBuyRequest;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BuyController extends Controller{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $buys = Buy::userFilter($userId)->with(['product', 'user'])->get();

        $buys->each(function ($buy) {
            $formatted_dt1 = Carbon::parse($buy->dateExpiry);
            $formatted_dt2 = Carbon::parse(now());
            $buy->diasPorvencer = $formatted_dt1->diffInDays($formatted_dt2);
        });

        $buys = $buys->filter(function ($buy) {
            return $buy->diasPorvencer > 0;
        });

        // Ordenar por días de vencimiento
        $buys = $buys->sortBy('diasPorvencer');

        return $buys->values(); // Restablecer las claves de índice
    }
    public function show(Buy $buy){ return $buy; }
    public function store(StoreBuyRequest $request){
        $buy = new Buy();
        $buy->user_id= $request->user()->id;
        $buy->product_id= $request->product_id;
        $buy->lote= $request->lote;
        $buy->quantity= $request->quantity;
        $buy->price= $request->price;
        $buy->total= $request->quantity * $request->price;
        $buy->dateExpiry= $request->dateExpiry;
        $buy->agencia_id= $request->user()->agencia_id;
        $buy->date= date("Y-m-d");
        $buy->time= date("H:i:s");
        $buy->save();

        $product = Product::find($request->product_id);
        $product->cantidad = $product->cantidad + $buy->quantity;
        $product->cantidadAlmacen = $product->cantidadAlmacen + $buy->quantity;
        $product->save();
        return Buy::with(['product','user'])->findOrFail($buy->id);
    }
    public function compraInsert(Request $request){
        $insertbuys = [];
        foreach ($request->buys as $buy) {
//            error_log(json_encode($buy));
            $buyNew = new Buy();
            $buyNew->user_id= $request->user()->id;
            $buyNew->product_id= $buy['id'];
            $buyNew->lote= $buy['lote'];
            $buyNew->quantity= $buy['cantidadCompra'];
            $buyNew->price= $buy['price'];
            $buyNew->total= $buy['cantidadCompra'] * $buy['price'];
            $buyNew->dateExpiry= $buy['fechaVencimiento'];
            $buyNew->agencia_id= $request->user()->agencia_id;
            $buyNew->factura= isset($request->factura) ? $request->factura : 0;
            $buyNew->date= date("Y-m-d");
            $buyNew->time= date("H:i:s");
            $buyNew->save();

            $product = Product::find($buy['id']);
            $product->cantidad = $product->cantidad + $buy['cantidadCompra'];
            if ($request->agencia_id = 0) {
                $product->cantidadAlmacen = $product->cantidadAlmacen + $buy['cantidadCompra'];
            }elseif ($request->agencia_id = 1) {
                $product->cantidadSucursal1 = $product->cantidadSucursal1 + $buy['cantidadCompra'];
            }elseif ($request->agencia_id = 2) {
                $product->cantidadSucursal2 = $product->cantidadSucursal2 + $buy['cantidadCompra'];
            }elseif ($request->agencia_id = 3) {
                $product->cantidadSucursal3 = $product->cantidadSucursal3 + $buy['cantidadCompra'];
            }elseif ($request->agencia_id = 4) {
                $product->cantidadSucursal4 = $product->cantidadSucursal4 + $buy['cantidadCompra'];
            }

            $product->precio = $buy['price'];
            $product->costo = $buy['price']/0.13;
            $product->save();
//            return Buy::with(['product','user'])->findOrFail($buy->id);
        }
        Buy::insert($insertbuys);
    }
    public function update(UpdateBuyRequest $request, Buy $buy){ return $buy->update($request->all()); }
    public function destroy(Buy $buy){ return $buy->delete(); }
}
