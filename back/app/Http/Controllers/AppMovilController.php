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

        $productos->getCollection()->transform(
            fn ($p) => $this->productoResumen($p, $agencias)
        );

        return $productos;
    }

    // GET /app/productos/{id} — detalle completo + productos similares
    public function productoDetalle($id)
    {
        $agencias = Agencia::where('status', 'ACTIVO')->orderBy('id')->get();

        $producto = Product::with('category')
            ->where('activo', 'ACTIVO')
            ->findOrFail($id);

        $similares = $this->productosRelacionados($producto);

        return response()->json([
            'producto'  => $this->productoResumen($producto, $agencias),
            'similares' => $similares
                ->map(fn ($p) => $this->productoResumen($p, $agencias))
                ->values(),
        ]);
    }

    /**
     * Productos relacionados por principio activo, con la misma lógica que la
     * tienda pública (DetalleProducto.vue): candidatos por tokens de la
     * composición o misma subcategoría, misma dosis obligatoria y similitud
     * difusa entre composiciones (umbral 0.55, o 0.35 si no hay composición).
     */
    private function productosRelacionados(Product $producto)
    {
        $comp = trim((string) $producto->composicion);
        if ($comp === '') {
            return collect();
        }

        $maxItems = 50;
        $subcat   = (int) $producto->subcategory_id ?: null;
        $baseDose = $this->claveDosis($comp);

        // Solo palabras (los números de dosis se comparan con claveDosis;
        // un token como "500" metería miles de productos al pool)
        $tokens = collect(explode(' ', $this->normalizarBusqueda($comp)))
            ->filter(fn ($t) => mb_strlen($t) >= 3 && !ctype_digit($t))
            ->unique()
            ->take(5);
        if ($tokens->isEmpty() && !$subcat) {
            return collect();
        }

        // Candidatos principales: coinciden con la composición por texto.
        // La subcategoría solo complementa (con su propio tope) para no
        // desplazar del pool a los que sí comparten principio activo.
        $pool = collect();
        if ($tokens->isNotEmpty()) {
            $pool = Product::with('category')
                ->where('activo', 'ACTIVO')
                ->where('id', '!=', $producto->id)
                ->where(function ($q) use ($tokens) {
                    foreach ($tokens as $t) {
                        $like = '%' . $t . '%';
                        $q->orWhere('composicion', 'like', $like)
                          ->orWhere('nombre', 'like', $like);
                    }
                })
                ->limit(600)
                ->get();
        }
        if ($subcat) {
            $pool = $pool->concat(
                Product::with('category')
                    ->where('activo', 'ACTIVO')
                    ->where('id', '!=', $producto->id)
                    ->where('subcategory_id', $subcat)
                    ->whereNotIn('id', $pool->pluck('id'))
                    ->limit(200)
                    ->get()
            );
        }

        $a      = $this->normalizarSimilitud($comp);
        $scored = [];
        foreach ($pool as $p) {
            $textoCand = trim((string) $p->composicion) !== ''
                ? (string) $p->composicion
                : (string) $p->nombre;

            // Misma dosis obligatoria (500mg no se relaciona con 200mg)
            if ($baseDose !== '' && $this->claveDosis($textoCand) !== $baseDose) {
                continue;
            }

            $hasComp = trim((string) $p->composicion) !== '';
            $s       = $this->similitud($a, $this->normalizarSimilitud($textoCand));
            $score   = $hasComp ? $s : min($s + 0.2, 0.5);
            $minimo  = $hasComp ? 0.55 : 0.35;
            if ($score < $minimo) {
                continue;
            }
            $scored[] = ['p' => $p, 'score' => $score];
        }

        usort($scored, function ($x, $y) use ($subcat) {
            if ($x['score'] !== $y['score']) {
                return $y['score'] <=> $x['score'];
            }
            $xs = $subcat && (int) $x['p']->subcategory_id === $subcat ? 1 : 0;
            $ys = $subcat && (int) $y['p']->subcategory_id === $subcat ? 1 : 0;
            if ($xs !== $ys) {
                return $ys <=> $xs;
            }
            return ((float) $x['p']->precio) <=> ((float) $y['p']->precio);
        });

        return collect(array_slice(array_column($scored, 'p'), 0, $maxItems));
    }

    // Minúsculas sin acentos, separa letra/número ("500MG" -> "500 mg")
    private function normalizarBusqueda(string $s): string
    {
        $s = strtr(mb_strtolower($s), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n',
        ]);
        $s = preg_replace('/([a-z])(\d)/', '$1 $2', $s);
        $s = preg_replace('/(\d)([a-z])/', '$1 $2', $s);
        $s = preg_replace('/[^a-z0-9\s]/', ' ', $s);

        return trim(preg_replace('/\s+/', ' ', $s));
    }

    // Normalización para comparar composiciones: alias y sin formas farmacéuticas
    private function normalizarSimilitud(string $s): string
    {
        $s = strtr(mb_strtolower($s), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n',
        ]);
        $s = preg_replace('/([a-z])(\d)/', '$1 $2', $s);
        $s = preg_replace('/(\d)([a-z])/', '$1 $2', $s);

        $alias = [
            'ibuproneo'    => 'ibuprofeno',
            'acetaminofen' => 'paracetamol',
            'dc'           => 'compresion directa',
            'sr'           => 'liberacion sostenida',
            'gr'           => 'gastroresistente',
            'reistente'    => 'gastroresistente',
        ];
        foreach ($alias as $k => $v) {
            $s = preg_replace('/\b' . $k . '\b/', $v, $s);
        }
        $s = preg_replace('/\bgastro\s+resistente\b/', 'gastroresistente', $s);

        $s = preg_replace('/[^a-z0-9\s]/', ' ', $s);
        $s = preg_replace('/\b(comprimidos?|tabletas?|capsulas?|solucion|jarabe|unguento|crema|spray)\b/', ' ', $s);

        return trim(preg_replace('/\s+/', ' ', $s));
    }

    // Firma de dosis: "PARACETAMOL 500 MG" -> "500mg" (ordenada si hay varias)
    private function claveDosis(string $s): string
    {
        preg_match_all('/\b(\d+(?:[.,]\d+)?)\s*(mg|ml|mcg|µg|ug|g|gr|kg|l|ui|%)\b/iu', $s, $m, PREG_SET_ORDER);
        $out = [];
        foreach ($m as $match) {
            $num   = (string) (float) str_replace(',', '.', $match[1]);
            $out[] = $num . mb_strtolower($match[2]);
        }
        sort($out);

        return implode('|', $out);
    }

    // Similitud tipo Jaccard entre tokens, tolerante a erratas (Levenshtein)
    private function similitud(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }
        $ta = array_values(array_unique(array_filter(explode(' ', $a))));
        $tb = array_values(array_unique(array_filter(explode(' ', $b))));
        if (!$ta || !$tb) {
            return 0.0;
        }

        $eq = function (string $x, string $y): bool {
            if ($x === $y) {
                return true;
            }
            $d = levenshtein($x, $y);
            $l = max(strlen($x), strlen($y));
            if ($l <= 4) {
                return $d <= 1;
            }
            if ($l <= 7) {
                return $d <= 2;
            }

            return $d <= 3;
        };

        $usados = [];
        $inter  = 0;
        foreach ($ta as $t) {
            foreach ($tb as $j => $cand) {
                if (isset($usados[$j])) {
                    continue;
                }
                if ($eq($t, $cand)) {
                    $usados[$j] = true;
                    $inter++;
                    break;
                }
            }
        }
        $union = count(array_unique(array_merge($ta, $tb)));

        return $union > 0 ? $inter / $union : 0.0;
    }

    /**
     * Contrato de producto para la app. El precio ya viene con el descuento
     * aplicado y precio_antes guarda el precio original cuando hay oferta.
     */
    private function productoResumen(Product $p, $agencias): array
    {
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

        $precio     = (float) $p->precio;
        $porcentaje = (float) ($p->porcentaje ?? 0);
        if ($porcentaje > 0) {
            $precioFinal = round($precio - ($precio * $porcentaje / 100), 2);
            $precioAntes = $precio;
        } else {
            $precioFinal = $precio;
            $precioAntes = (float) ($p->precioAntes ?? 0);
        }

        return [
            'id'                 => $p->id,
            'nombre'             => $p->nombre,
            'categoria'          => $p->category->name ?? 'General',
            'category_id'        => $p->category_id,
            'presentacion'       => $p->unidad,
            'marca'              => $p->marca,
            'precio'             => $precioFinal,
            'precio_antes'       => $precioAntes,
            'en_oferta'          => (bool) $p->en_oferta,
            'porcentaje'         => $porcentaje,
            'imagen'             => $img,
            'descripcion'        => $p->descripcion,
            'registro_sanitario' => $p->registroSanitario,
            'origen'             => $p->paisOrigen,
            'composicion'        => $p->composicion,
            'distribuidora'      => $p->distribuidora,
            'stocks'             => $stocks,
            'stock_total'        => $stocks->sum('cantidad'),
        ];
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
