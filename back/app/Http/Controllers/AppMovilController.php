<?php

namespace App\Http\Controllers;

use App\Models\Agencia;
use App\Models\Category;
use App\Models\Pedido;
use App\Models\PedidoDetail;
use App\Models\PedidoModificacion;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * API para la app móvil de pre-venta (Flutter).
 * Todas las rutas viven bajo /api/app/* y requieren auth:sanctum.
 */
class AppMovilController extends Controller
{
    const UMBRAL_STOCK_BAJO = 20;

    // GET /app/config — catálogo base que la app carga al iniciar
    public function config()
    {
        return response()->json([
            'categorias'        => Category::orderBy('name')->get(['id', 'name']),
            'sucursales'        => Agencia::where('status', 'ACTIVO')->orderBy('id')->get(),
            'umbral_stock_bajo' => self::UMBRAL_STOCK_BAJO,
        ]);
    }

    // GET /app/productos?search=&category_id=&ofertas=1&per_page=&page=
    public function productos(Request $request)
    {
        $search     = trim((string) $request->get('search', ''));
        $categoryId = (int) $request->get('category_id', 0);
        $ofertas    = $request->boolean('ofertas');
        $perPage    = max(5, min(50, (int) $request->get('per_page', 30)));

        $agencias = Agencia::where('status', 'ACTIVO')->orderBy('id')->get();

        $query = Product::query()
            ->where('activo', 'ACTIVO')
            ->with('category');

        if ($search !== '') {
            $like = '%' . str_replace(' ', '%', strtoupper($search)) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('nombre', 'like', $like)
                  ->orWhere('composicion', 'like', $like)
                  ->orWhere('marca', 'like', $like);
            });
        }
        if ($categoryId > 0) {
            $query->where('category_id', $categoryId);
        }
        if ($ofertas) {
            $query->where('en_oferta', 1);
        }

        $productos = $query->orderByDesc('en_oferta')->orderBy('nombre')->paginate($perPage);

        $productos->getCollection()->transform(function ($p) use ($agencias) {
            $stocks = $agencias->map(fn ($a) => [
                'agencia_id' => $a->id,
                'agencia'    => $a->nombre,
                'cantidad'   => (int) ($p->{'cantidadSucursal' . $a->id} ?? 0),
            ])->values();

            // Imagen: solo si existe en public/images (o es URL absoluta)
            $img = $p->imagen ?? '';
            $isAbsolute = is_string($img) && preg_match('#^https?://#i', $img);
            if (!$isAbsolute && (empty($img) || !file_exists(public_path('images/' . $img)))) {
                $img = null;
            }

            return [
                'id'           => $p->id,
                'nombre'       => $p->nombre,
                'categoria'    => $p->category->name ?? 'General',
                'category_id'  => $p->category_id,
                'presentacion' => $p->unidad,
                'marca'        => $p->marca,
                'precio'       => (float) $p->precio,
                'precio_antes' => (float) ($p->precioAntes ?? 0),
                'en_oferta'    => (bool) $p->en_oferta,
                'porcentaje'   => (float) ($p->porcentaje ?? 0),
                'imagen'       => $img,
                'stocks'       => $stocks,
                'stock_total'  => $stocks->sum('cantidad'),
            ];
        });

        return $productos;
    }

    // GET /app/pedidos — pedidos de la sucursal del usuario (admin ve todos)
    public function pedidos(Request $request)
    {
        $user  = $request->user();
        $query = Pedido::with(['agencia', 'detalles.product'])->orderByDesc('id');

        if ($user->id != 1) {
            $query->where('agencia_id', $user->agencia_id);
        }

        $pedidos = $query->paginate(max(5, min(50, (int) $request->get('per_page', 20))));

        $pedidos->getCollection()->transform(fn ($pd) => $this->pedidoResumen($pd));

        return $pedidos;
    }

    // POST /app/pedidos — registra un pedido interno desde la app
    public function pedidoStore(Request $request)
    {
        $request->validate([
            'agencia_id'            => 'required|integer|exists:agencias,id',
            'prioridad'             => 'nullable|in:NORMAL,URGENTE',
            'observacion'           => 'nullable|string|max:500',
            'detalles'              => 'required|array|min:1',
            'detalles.*.product_id' => 'required|exists:products,id',
            'detalles.*.cantidad'   => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $pedido = Pedido::create([
                'agencia_id'   => $request->agencia_id,
                'user_id'      => $request->user()->id,
                'fecha_pedido' => now(),
                'estado'       => 'PENDIENTE',
                'prioridad'    => $request->prioridad ?? 'NORMAL',
                'observacion'  => $request->observacion,
            ]);

            foreach ($request->detalles as $detalle) {
                PedidoDetail::create([
                    'pedido_id'  => $pedido->id,
                    'product_id' => $detalle['product_id'],
                    'cantidad'   => $detalle['cantidad'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Pedido registrado con éxito',
                'pedido'  => $this->pedidoResumen($pedido->fresh(['agencia', 'detalles.product'])),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al guardar el pedido',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // POST /app/pedidos/{id}/recuperar — marca el pedido listo para venta en caja
    public function pedidoRecuperar(Request $request, $id)
    {
        $pedido = Pedido::with(['agencia', 'detalles.product'])->findOrFail($id);
        $user   = $request->user();

        if ($user->id != 1 && $pedido->agencia_id != $user->agencia_id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ($pedido->estado === 'RECUPERADO') {
            return response()->json(['message' => 'El pedido ya fue recuperado'], 422);
        }

        try {
            DB::beginTransaction();

            PedidoModificacion::create([
                'pedido_id'       => $pedido->id,
                'user_id'         => $user->id,
                'accion'          => 'RECUPERADO',
                'estado_anterior' => $pedido->estado,
                'estado_nuevo'    => 'RECUPERADO',
                'observacion'     => 'Recuperado para venta desde la app móvil',
            ]);

            $pedido->estado = 'RECUPERADO';
            $pedido->save();

            DB::commit();

            return response()->json([
                'message' => 'Pedido recuperado para venta',
                'pedido'  => $this->pedidoResumen($pedido),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al recuperar el pedido',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function pedidoResumen(Pedido $pd): array
    {
        $detalles = $pd->detalles ?? collect();
        $total    = $detalles->sum(fn ($d) => $d->cantidad * (float) ($d->product->precio ?? 0));

        return [
            'id'          => $pd->id,
            'codigo'      => 'PED-' . str_pad($pd->id, 4, '0', STR_PAD_LEFT),
            'sucursal'    => $pd->agencia->nombre ?? '',
            'fecha'       => optional($pd->created_at)->format('d/m/Y H:i'),
            'items'       => (int) $detalles->sum('cantidad'),
            'total'       => round($total, 2),
            'estado'      => $pd->estado,
            'prioridad'   => $pd->prioridad ?? 'NORMAL',
            'observacion' => $pd->observacion,
            'detalles'    => $detalles->map(fn ($d) => [
                'producto' => $d->product->nombre ?? '',
                'cantidad' => (int) $d->cantidad,
                'precio'   => (float) ($d->product->precio ?? 0),
            ])->values(),
        ];
    }
}
