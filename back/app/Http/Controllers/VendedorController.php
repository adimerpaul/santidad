<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vendedor;

class VendedorController extends Controller
{
    // Listar TODOS los vendedores (con su proveedor) para la tabla de gestión
    public function index()
    {
        return Vendedor::with('client')->orderBy('id', 'desc')->get();
    }

    // Método especial para el WhatsApp (HistorialPedidos)
    public function getByProvider($provider_id)
    {
        return Vendedor::where('client_id', $provider_id)->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'celular' => 'required',
            'client_id' => 'required|exists:clients,id'
        ]);

        return Vendedor::create($request->all());
    }

    public function update(Request $request, $id)
    {
        $vendedor = Vendedor::find($id);
        
        $request->validate([
            'nombre' => 'required',
            'celular' => 'required',
            'client_id' => 'required|exists:clients,id'
        ]);

        $vendedor->update($request->all());
        return $vendedor;
    }

    public function destroy($id)
    {
        $vendedor = Vendedor::find($id);
        $vendedor->delete();
        return response()->json(['message' => 'Eliminado correctamente']);
    }
}