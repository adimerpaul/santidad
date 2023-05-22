<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;

class ClientController extends Controller{
    public function index(){ return Client::where('clienteProveedor', 'Cliente')->orderBy('nombreRazonSocial')->get(); }
    public function providers(){ return Client::where('clienteProveedor', 'Proveedor')->orderBy('nombreRazonSocial')->get(); }
    public function store(StoreClientRequest $request){ return Client::create($request->all()); }
    public function show(Client $client){ return $client; }
    public function update(UpdateClientRequest $request, Client $client){ return $client->update($request->all()); }
    public function destroy(Client $client){ return $client->delete(); }
}
