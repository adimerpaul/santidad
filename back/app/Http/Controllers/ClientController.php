<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use Illuminate\Http\Request;

class ClientController extends Controller{
    public function index(Request $request){
//        "nombreRazonSocial",
//        "codigoTipoDocumentoIdentidad",
//        "numeroDocumento",
//        "complemento",
//        "email",
//        "telefono",
//        "clienteProveedor",
        $search = $request->get('search')=='undefined'||$request->get('search')=="null"?'':$request->get('search');
        $clients = Client::where('clienteProveedor', 'Cliente')
            ->orderBy('nombreRazonSocial')
            ->where('nombreRazonSocial', 'LIKE', "%$search%")
            ->orWhere('numeroDocumento', 'LIKE', "%$search%")
            ->orWhere('telefono', 'LIKE', "%$search%")
            ->orWhere('email', 'LIKE', "%$search%")
            ->paginate(15);
        return $clients;
    }
    public function indexProvider(Request $request){
        $search = $request->get('search')=='undefined'||$request->get('search')=="null"?'':$request->get('search');
        $clients = Client::where('clienteProveedor', 'Proveedor')
            ->orderBy('nombreRazonSocial')
            ->whereRaw("(nombreRazonSocial LIKE '%$search%' OR numeroDocumento LIKE '%$search%' OR telefono LIKE '%$search%' OR email LIKE '%$search%')")
            ->paginate(15);
        return $clients;
    }
    public function providers(){
        return Client::where('clienteProveedor', 'Proveedor')
            ->orderBy('nombreRazonSocial')->get();
    }
    public function store(StoreClientRequest $request){ return Client::create($request->all()); }
    public function show(Client $client){ return $client; }
    public function update(UpdateClientRequest $request, $id){
        $client = Client::findOrFail($id);
        return $client->update($request->all());
    }
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
