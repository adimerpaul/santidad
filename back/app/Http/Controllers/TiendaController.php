<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class TiendaController extends Controller{
    public function productos(Request $request){
        $search = $request->search;
        $productos = Product::whereActivo('ACTIVO')
            ->where('nombre','like','%'.$search.'%')
            ->orWhere('descripcion','like','%'.$search.'%');
        $productos->each(function ($product) {
            if (!file_exists(public_path() . '/images/' . $product->image)) {
                $product->image = 'productDefault.jpg';
            }
        });
        return $productos->paginate(12);
    }
}
