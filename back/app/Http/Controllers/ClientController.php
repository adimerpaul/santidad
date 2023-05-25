<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use Illuminate\Http\Request;

class ClientController extends Controller{
    public function index(){ return Client::where('clienteProveedor', 'Cliente')->orderBy('nombreRazonSocial')->get(); }
    public function providers(){ return Client::where('clienteProveedor', 'Proveedor')->orderBy('nombreRazonSocial')->get(); }
    public function store(StoreClientRequest $request){ return Client::create($request->all()); }
    public function show(Client $client){ return $client; }
    public function update(UpdateClientRequest $request, Client $client){ return $client->update($request->all()); }
    public function destroy(Client $client){ return $client->delete(); }

    public function searchClient(Request $request)
    {
//        return $request->has('complemento');
        if ($request->has('complemento') && $request->complemento != '') {
            $clients = Client::where('numeroDocumento',  $request->numeroDocumento)->where('complemento',  $request->complemento)->first();
        } else {
            $clients = Client::where('numeroDocumento',  $request->numeroDocumento)->where('complemento','')->first();
        }
        return $clients;
    }
}
