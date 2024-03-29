<?php

namespace App\Http\Controllers;

use App\Models\Unid;
use App\Http\Requests\StoreUnidRequest;
use App\Http\Requests\UpdateUnidRequest;

class UnidController extends Controller{
    public function index(){
        $unids= Unid::orderBy('nombre')->get();
        return $unids->pluck('nombre');
    }
    public function unidAll(){
        $unids= Unid::all();
        return $unids;
    }
    public function store(StoreUnidRequest $request){
        //verificamos si existe el mismo nombre
        $unid = Unid::where('nombre', $request->nombre)->first();
        if($unid){
            return response()->json(['error' => 'Ya existe una unidad con el mismo nombre'], 409);
        }
        $unid = Unid::create($request->all());
        return response()->json($unid, 201);
    }
    public function show(Unid $unid){
        return $unid;
    }
    public function update(UpdateUnidRequest $request, Unid $unid){
        $unid->update($request->all());
        return response()->json($unid, 200);
    }
    public function destroy(Unid $unid){
        $unid->delete();
        return response()->json(null, 204);
    }
}
