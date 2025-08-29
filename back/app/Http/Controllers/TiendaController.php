<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class TiendaController extends Controller{
    function sucursales(){
        return \App\Models\Agencia::where('status','ACTIVO')->get();
    }
  public function productos(Request $request)
{
    // ➜ si no mandas search, que no rompa; y permite pedir más ítems en la página 1
    $search   = trim((string) $request->get('search', ''));
    $paginate = (int) ($request->get('per_page') ?? 12 * 5);

    $productos = Product::query()
        ->where('activo', 'ACTIVO')
        // ➜ filtra solo si hay search
        ->when($search !== '', function ($q) use ($search) {
            $like = "%{$search}%";
            $q->where(function ($qq) use ($like) {
                $qq->where('nombre', 'like', $like)
                   ->orWhere('descripcion', 'like', $like)
                   ->orWhere('composicion', 'like', $like);
            });
        })
        // ➜ clave: primero los que están en oferta para que aparezcan en page=1
        ->orderBy('en_oferta', 'desc')
        ->orderByDesc('id')
        ->paginate($paginate);

    // ➜ imagen por defecto si no existe + forzar boolean en_oferta (por prolijidad)
    $productos->getCollection()->transform(function ($product) {
        $img = $product->imagen ?? '';
        $isAbsolute = is_string($img) && preg_match('#^https?://#i', $img);
        if (!$isAbsolute) {
            if (empty($img) || !file_exists(public_path('images/' . $img))) {
                $product->imagen = 'productDefault.jpg';
            }
        }
        $product->en_oferta = (bool) $product->en_oferta;
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

    // Si es URL absoluta, la respetamos
    $isAbsolute = is_string($img) && preg_match('#^https?://#i', $img);

    if (!$isAbsolute) {
        if (empty($img) || !file_exists(public_path('images/' . $img))) {
            $producto->imagen = 'productDefault.jpg';   // ← fallback
        }
    }

    return response()->json($producto);
}

}