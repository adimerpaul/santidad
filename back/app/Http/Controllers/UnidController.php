<?php

namespace App\Http\Controllers;

use App\Models\Unid;
use App\Http\Requests\StoreUnidRequest;
use App\Http\Requests\UpdateUnidRequest;

class UnidController extends Controller{
    public function index(){
        $unids= Unid::all();
        ///return array name unids
        return $unids->pluck('nombre');
    }
    public function store(StoreUnidRequest $request){
        $unid = Unid::create($request->all());
        return response()->json($unid, 201);
    }
}
