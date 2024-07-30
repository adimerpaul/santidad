<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TiendaController extends Controller{
    function sucursales(){
        return \App\Models\Agencia::where('status','ACTIVO')->get();
    }
    public function productos(Request $request) {
        $search = $request->search;
        $paginate = 12*5;

        // Construcción de la consulta con paginación
        $productos = Product::where('activo', 'ACTIVO')
            ->where(function($query) use ($search) {
                $query->where('nombre', 'like', '%' . $search . '%')
                    ->orWhere('descripcion', 'like', '%' . $search . '%');
            })
            ->paginate($paginate); // Paginación automática

        // Verificación y cambio de imagen si no existe
        $productos->getCollection()->transform(function ($product) {
            if (!file_exists(public_path('images/' . $product->imagen))) {
                $product->imagen = 'productDefault.jpg';
                error_log('No existe la imagen');
            }
            return $product;
        });

        return $productos;
    }
    function productosId($id){
        $producto = Product::where('id',$id)->first();
        if (!file_exists(public_path() . '/images/' . $producto->image)) {
            $producto->image = 'productDefault.jpg';
        }
        return $producto;
    }
}
