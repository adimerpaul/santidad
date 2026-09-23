<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\PromotionPricingService;
use Illuminate\Http\Request;

class TiendaController extends Controller
{
    public function __construct(private readonly PromotionPricingService $promotionPricing)
    {
    }

    function sucursales(){
        return \App\Models\Agencia::where('status','ACTIVO')->get();
    }

    public function productos(Request $request)
    {
        $search   = trim((string) $request->get('search', ''));
        $paginate = (int) ($request->get('per_page') ?? 12 * 5);
        $agenciaId = (int) $request->get('agencia_id', 0);
        $categoryId = (int) $request->get('category_id', 0);
        $subcategoryId = (int) $request->get('subcategory_id', 0);
        $ofertas = $request->boolean('ofertas');
        $scopeOfertas = $ofertas
            ? $this->promotionPricing->activeScopeIds('web', $agenciaId ?: null)
            : ['product_ids' => [], 'category_ids' => []];

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
            ->when($categoryId > 0, fn ($q) => $q->where('category_id', $categoryId))
            ->when($subcategoryId > 0, fn ($q) => $q->where('subcategory_id', $subcategoryId))
            ->when($ofertas, function ($q) use ($scopeOfertas) {
                $q->where(function ($ofertasQuery) use ($scopeOfertas) {
                    $ofertasQuery->where('en_oferta', 1);

                    if (!empty($scopeOfertas['all_categories']) || !empty($scopeOfertas['product_ids']) || !empty($scopeOfertas['category_ids'])) {
                        $ofertasQuery->orWhere(function ($promotionQuery) use ($scopeOfertas) {
                            $promotionQuery->where(function ($scopeQuery) use ($scopeOfertas) {
                                if (!empty($scopeOfertas['all_categories'])) {
                                    $scopeQuery->whereRaw('1 = 1');
                                    return;
                                }
                                if (!empty($scopeOfertas['product_ids'])) {
                                    $scopeQuery->whereIn('id', $scopeOfertas['product_ids']);
                                }
                                if (!empty($scopeOfertas['category_ids'])) {
                                    $method = !empty($scopeOfertas['product_ids']) ? 'orWhereIn' : 'whereIn';
                                    $scopeQuery->{$method}('category_id', $scopeOfertas['category_ids']);
                                }
                            });

                            if (!empty($scopeOfertas['excluded_subcategory_ids'])) {
                                $promotionQuery->where(function ($allowedQuery) use ($scopeOfertas) {
                                    $allowedQuery->whereNull('subcategory_id')
                                        ->orWhereNotIn('subcategory_id', $scopeOfertas['excluded_subcategory_ids']);
                                });
                            }
                        });
                    }
                });
            })
            ->orderBy('en_oferta', 'desc')
            ->orderByDesc('id')
            ->paginate($paginate);

        $productos->getCollection()->transform(function ($product) use ($agenciaId) {
            $img = $product->imagen ?? '';
            $isAbsolute = is_string($img) && preg_match('#^https?://#i', $img);
            if (!$isAbsolute) {
                if (empty($img) || !file_exists(public_path('images/' . $img))) {
                    $product->imagen = 'productDefault.jpg';
                }
            }
            $product->en_oferta = (bool) $product->en_oferta;
            $pricing = $this->promotionPricing->resolve($product, 'web', $agenciaId ?: null);
            $product->en_oferta = $product->en_oferta || $pricing['mostrar_en_ofertas'];
            $product->setAttribute('precioVenta', $pricing['precio_venta']);
            $product->setAttribute('porcentajeEfectivo', $pricing['porcentaje']);
            $product->setAttribute('promocionId', $pricing['promocion_id']);
            $product->setAttribute('promocion', $pricing['promocion_nombre']);
            // Gracias al accessor en el modelo, activo ya viene como 'ACTIVO'/'INACTIVO'
            return $product;
        });

        return $productos;
    }

    public function productosId(Request $request, $id)
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

        $agenciaId = (int) $request->get('agencia_id', 0);
        $pricing = $this->promotionPricing->resolve($producto, 'web', $agenciaId ?: null);
        $producto->en_oferta = (bool) $producto->en_oferta || $pricing['mostrar_en_ofertas'];
        $producto->setAttribute('precioVenta', $pricing['precio_venta']);
        $producto->setAttribute('porcentajeEfectivo', $pricing['porcentaje']);
        $producto->setAttribute('promocionId', $pricing['promocion_id']);
        $producto->setAttribute('promocion', $pricing['promocion_nombre']);

        return response()->json($producto);
    }
}
