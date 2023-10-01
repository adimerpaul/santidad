<?php

namespace App\Http\Controllers;

use App\Models\Buy;
use App\Http\Requests\StoreBuyRequest;
use App\Http\Requests\UpdateBuyRequest;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BuyController extends Controller{
    public function index(Request $request){
        if ($request->user()->id == 1) {
            $buys = Buy::with(['product','user'])->get();
        }else{
            $buys = Buy::where('agencia_id', $request->user()->agencia_id)
                ->with(['product','user'])->get();
        }
        $buys->each(function ($buy) {
            $formatted_dt1=Carbon::parse($buy->dateExpiry);

            $formatted_dt2=Carbon::parse(date("Y-m-d"));
            $buy->diasPorvencer = $formatted_dt1->diffInDays($formatted_dt2);
        });
        return $buys;
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
    public function update(UpdateBuyRequest $request, Buy $buy){ return $buy->update($request->all()); }
    public function destroy(Buy $buy){ return $buy->delete(); }
}
