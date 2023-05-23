<?php

namespace App\Http\Controllers;

use App\Models\Agencia;
use App\Http\Requests\StoreAgenciaRequest;
use App\Http\Requests\UpdateAgenciaRequest;

class AgenciaController extends Controller{
    public function index(){ return Agencia::all(); }
    public function store(StoreAgenciaRequest $request){ return Agencia::create($request->all()); }
    public function show(Agencia $agencia){ return $agencia; }
    public function update(UpdateAgenciaRequest $request, Agencia $agencia){ return $agencia->update($request->all()); }
    public function destroy(Agencia $agencia){ return $agencia->delete(); }
}
