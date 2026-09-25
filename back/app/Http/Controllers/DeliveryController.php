<?php

namespace App\Http\Controllers;

use App\Models\Agencia;
use App\Models\Product;
use App\Services\PromotionPricingService;
use Illuminate\Http\Request;

/**
 * API pública para empresas de delivery.
 * Rutas bajo /api/delivery/* sin autenticación (solo throttle).
 * Precios con los descuentos del canal web (mismos que la tienda pública).
 */
class DeliveryController extends Controller
{
    public function __construct(private readonly PromotionPricingService $promotionPricing)
    {
    }

    // GET /delivery/sucursales
    public function sucursales()
    {
        return response()->json(
            $this->agencias()->map(fn ($a) => [
                'id'        => $a->id,
                'nombre'    => $a->nombre,
                'direccion' => $a->direccion,
                'telefono'  => $a->telefono,
                'horario'   => $a->horario,
                'latitud'   => $a->latitud,
                'longitud'  => $a->longitud,
            ])->values()
        );
    }

    // GET /delivery/productos?search=&category_id=&agencia_id=&disponibles=1&per_page=&page=
    public function productos(Request $request)
    {
        $search      = trim((string) $request->get('search', ''));
        $categoryId  = (int) $request->get('category_id', 0);
        $agenciaId   = (int) $request->get('agencia_id', 0);
        $disponibles = $request->boolean('disponibles');
        $perPage     = max(5, min(100, (int) $request->get('per_page', 30)));

        $agencias = $this->agencias();
        $agencia  = $agencias->firstWhere('id', $agenciaId);

        $query = Product::query()->where('activo', 'ACTIVO');

        if ($search !== '') {
            $like = '%' . str_replace(' ', '%', $search) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('nombre', 'like', $like)
                  ->orWhere('composicion', 'like', $like)
                  ->orWhere('marca', 'like', $like);
            });
        }
        if ($categoryId > 0) {
            $query->where('category_id', $categoryId);
        }
        if ($disponibles) {
            // Con sucursal: stock en esa sucursal; sin sucursal: stock en alguna
            $columnas = $agencia
                ? ['cantidadSucursal' . $agencia->id]
                : $agencias->map(fn ($a) => 'cantidadSucursal' . $a->id)->all();
            $query->where(function ($q) use ($columnas) {
                foreach ($columnas as $col) {
                    $q->orWhere($col, '>', 0);
                }
            });
        }

        $productos = $query->orderBy('nombre')->paginate($perPage);

        $productos->getCollection()->transform(
            fn ($p) => $this->productoDelivery($p, $agencias, $agencia?->id)
        );

        return $productos;
    }

    // GET /delivery/productos/{id}?agencia_id=
    public function producto(Request $request, $id)
    {
        $agencias  = $this->agencias();
        $agenciaId = (int) $request->get('agencia_id', 0);

        $producto = Product::where('activo', 'ACTIVO')->findOrFail($id);

        return response()->json(
            $this->productoDelivery($producto, $agencias, $agencias->firstWhere('id', $agenciaId)?->id)
        );
    }

    private function agencias()
    {
        return Agencia::where('status', 'ACTIVO')->orderBy('id')->get();
    }

    private function productoDelivery(Product $p, $agencias, ?int $agenciaId): array
    {
        $pricing = $this->promotionPricing->resolve($p, 'web', $agenciaId);

        $disponibilidad = $agencias->map(function ($a) use ($p) {
            $cantidad = max(0, (int) ($p->{'cantidadSucursal' . $a->id} ?? 0));

            return [
                'sucursal_id' => $a->id,
                'sucursal'    => $a->nombre,
                'cantidad'    => $cantidad,
                'disponible'  => $cantidad > 0,
            ];
        })->values();

        return [
            'id'                   => $p->id,
            'nombre'               => $p->nombre,
            'imagen'               => $this->imagenUrl($p->imagen),
            'precio'               => $pricing['precio_original'],
            'descuento_porcentaje' => $pricing['porcentaje'],
            'descuento_monto'      => round($pricing['precio_original'] - $pricing['precio_venta'], 2),
            'precio_venta'         => $pricing['precio_venta'],
            'disponibilidad'       => $disponibilidad,
        ];
    }

    // URL absoluta de la imagen, o la imagen por defecto si no existe
    private function imagenUrl(?string $img): string
    {
        $img = (string) $img;
        if (preg_match('#^https?://#i', $img)) {
            return $img;
        }
        if ($img === '' || !file_exists(public_path('images/' . $img))) {
            $img = 'productDefault.jpg';
        }

        return asset('images/' . rawurlencode($img));
    }
}
