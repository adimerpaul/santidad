<?php

namespace App\Http\Controllers;

use App\Models\Buy;
use App\Http\Requests\StoreBuyRequest;
use App\Http\Requests\UpdateBuyRequest;
use Illuminate\Http\Request;

class BuyController extends Controller{
    public function index(Request $request){
        if ($request->user()->id == 1) {
            return Buy::with(['product','user'])->get();
        }else{
            return Buy::where('agencia_id', $request->user()->agencia_id)
                ->with(['product','user'])->get();
        }
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
        return $buy;
    }
    public function update(UpdateBuyRequest $request, Buy $buy){ return $buy->update($request->all()); }
    public function destroy(Buy $buy){ return $buy->delete(); }
}
