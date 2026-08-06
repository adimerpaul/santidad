<?php

namespace App\Http\Controllers;

use App\Models\Agencia;
use App\Models\Category;
use App\Models\Client;
use App\Models\Document;
use App\Models\Subcategory;
use App\Models\Unid;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Catálogos de la aplicación en una sola petición.
 *
 * Las páginas necesitaban 4 o 5 llamadas sueltas (categories, subcategories,
 * agencias, documents, providers, user) solo para llenar sus selects. Aquí se
 * piden juntas y el front las cachea por sesión, así el back recibe una sola
 * petición en vez de una por lista y por página.
 *
 * Cada clave devuelve exactamente la misma forma que su endpoint individual,
 * que se mantiene disponible para el resto de pantallas.
 */
class CatalogoController extends Controller
{
    /**
     * Fuentes disponibles. La clave es lo que el cliente pide en `include`.
     */
    private function fuentes(): array
    {
        return [
            'categories' => fn () => Category::all(),

            'subcategories' => function () {
                $subcategories = Subcategory::with('category')
                    ->orderBy('category_id')
                    ->get();

                $subcategories->each(function ($subcategory) {
                    $subcategory->nameComplete = $subcategory->category->name . ' ' . $subcategory->name;
                });

                return $subcategories;
            },

            'agencias' => fn () => Agencia::all(),

            'documents' => fn () => Document::all(),

            'unidades' => fn () => Unid::all(),

            'providers' => fn () => Client::where('clienteProveedor', 'Proveedor')
                ->orderBy('nombreRazonSocial')
                ->with('vendedores')
                ->get(),

            'users' => fn () => User::with('agencia')->get(),
        ];
    }

    public function index(Request $request)
    {
        $fuentes = $this->fuentes();

        // Sin `include` se devuelve todo; con él, solo lo pedido. El front pide
        // únicamente lo que aún no tiene en caché.
        $pedidas = $request->filled('include')
            ? array_filter(array_map('trim', explode(',', $request->input('include'))))
            : array_keys($fuentes);

        $desconocidas = array_diff($pedidas, array_keys($fuentes));
        if (!empty($desconocidas)) {
            return response()->json([
                'message' => 'Catálogo no reconocido: ' . implode(', ', $desconocidas),
                'disponibles' => array_keys($fuentes),
            ], 422);
        }

        $respuesta = [];
        foreach ($pedidas as $clave) {
            $respuesta[$clave] = $fuentes[$clave]();
        }

        return response()->json($respuesta);
    }
}
