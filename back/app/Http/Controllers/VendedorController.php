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

        if (strlen((string) $request->celular) !== 8) {
            return response()->json(['message' => 'debe ser un numero valido de 8 numeros'], 400);
        }

        $existe = \App\Models\Vendedor::where('celular', $request->celular)->first();
        if ($existe) {
            if ($request->user()->id != 1) {
                return response()->json(['message' => 'Numero ya existente, contactarse con administracion'], 400);
            }
        }

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

        if (strlen((string) $request->celular) !== 8) {
            return response()->json(['message' => 'debe ser un numero valido de 8 numeros'], 400);
        }

        $existe = \App\Models\Vendedor::where('celular', $request->celular)->where('id', '!=', $id)->first();
        if ($existe) {
            if ($request->user()->id != 1) {
                return response()->json(['message' => 'no se puede, contactese con el administrador, y solo el admi puede hacerlo'], 400);
            }
        }

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