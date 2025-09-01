<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class TiendaController extends Controller
{
    function sucursales(){
        return \App\Models\Agencia::where('status','ACTIVO')->get();
    }

    public function productos(Request $request)
    {
        $search   = trim((string) $request->get('search', ''));
        $paginate = (int) ($request->get('per_page') ?? 12 * 5);

        $productos = Product::query()
            // 🔧 tolerante: ACTIVO como texto y 1/true por si quedara histórico
            ->where(function ($q) {
                $q->where('activo', 'ACTIVO')
                  ->orWhere('activo', 1)
                  ->orWhere('activo', '1')
                  ->orWhere('activo', true)
                  ->orWhereIn('activo', ['true', 'TRUE']);
            })
            ->when($search !== '', function ($q) use ($search) {
                $like = "%{$search}%";
                $q->where(function ($qq) use ($like) {
                    $qq->where('nombre', 'like', $like)
                       ->orWhere('descripcion', 'like', $like)
                       ->orWhere('composicion', 'like', $like);
                });
            })
            ->orderBy('en_oferta', 'desc')
            ->orderByDesc('id')
            ->paginate($paginate);

        $productos->getCollection()->transform(function ($product) {
            $img = $product->imagen ?? '';
            $isAbsolute = is_string($img) && preg_match('#^https?://#i', $img);
            if (!$isAbsolute) {
                if (empty($img) || !file_exists(public_path('images/' . $img))) {
                    $product->imagen = 'productDefault.jpg';
                }
            }
            $product->en_oferta = (bool) $product->en_oferta;
            // Gracias al accessor en el modelo, activo ya viene como 'ACTIVO'/'INACTIVO'
            return $product;
        });

        return $productos;
    }

    public function productosId($id)
    {
        $producto = Product::find($id);
        if (!$producto) {
            return response()->json(['message' => 'Producto no encontrado tienda controller'], 404);
        }

        $img = $producto->imagen ?? '';
        $isAbsolute = is_string($img) && preg_match('#^https?://#i', $img);

        if (!$isAbsolute) {
            if (empty($img) || !file_exists(public_path('images/' . $img))) {
                $producto->imagen = 'productDefault.jpg';
            }
        }

        return response()->json($producto);
    }
}
