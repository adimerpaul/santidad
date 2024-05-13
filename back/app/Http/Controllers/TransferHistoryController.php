<?php

namespace App\Http\Controllers;

use App\Models\TransferHistory;
use Illuminate\Http\Request;

class TransferHistoryController extends Controller{
    public function historySucursal(Request $request){
        $product_id = $request->id;
        $agencia_id = $request->sucursal;
        $history = TransferHistory::
//        where('producto_id', $product_id)->
            whereRaw("(agencia_id_origen = $agencia_id or agencia_id_destino = $agencia_id) and producto_id = $product_id")
            ->with(['user', 'agenciaOrigen', 'agenciaDestino', 'producto'])
            ->orderBy('id', 'desc')
            ->get();
        return response()->json($history);
    }
    public function historySucursalProduct(Request $request){
        $product_id = $request->id;
        $history = TransferHistory::where('producto_id', $product_id)
            ->with(['user', 'agenciaOrigen', 'agenciaDestino', 'producto'])
            ->orderBy('id', 'desc')
            ->get();
        return response()->json($history);
    }
}
