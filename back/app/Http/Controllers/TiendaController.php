<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class TiendaController extends Controller{
    public function productos(){
        $productos = Product::whereActivo('ACTIVO')->get();
        return response()->json($productos);
    }
}
