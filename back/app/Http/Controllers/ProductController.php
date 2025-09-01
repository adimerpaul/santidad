<?php

namespace App\Http\Controllers;

use App\Models\Agencia;
use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\TransferHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Notificacion;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller
{
    public function verificarStockVenta(Request $request)
    {
        $productos = $request->input('productos', []);
        $agencia_id = $request->input('agencia_id');

        $errores = [];

        foreach ($productos as $item) {
            $producto = Product::find($item['id']);
            if (!$producto) {
                $errores[] = "Producto con ID {$item['id']} no encontrado.";
                continue;
            }

            $campoCantidad = $agencia_id ? 'cantidadSucursal' . $agencia_id : 'cantidadAlmacen';
            $stockDisponible = $producto->$campoCantidad ?? 0;

            if ($item['cantidadVenta'] > $stockDisponible) {
                $errores[] = "Stock insuficiente para '{$producto->nombre}'. Solo hay {$stockDisponible} disponibles.";
            }
        }

        if (!empty($errores)) {
            return response()->json(['errores' => $errores], 400);
        }

        return response()->json(['message' => 'Stock verificado correctamente']);
    }

    public function verificarStock(Request $request, $id)
    {
        $producto = Product::find($id);

        if (!$producto) {
            return response()->json(['error' => 'Producto no encontrado Product controller'], 404);
        }

        $agencia_id = $request->input('agencia_id');
        $campo = $agencia_id ? 'cantidadSucursal' . $agencia_id : 'cantidadAlmacen';

        $stock = $producto->$campo ?? 0;

        return response()->json([
            'stock' => $stock
        ]);
    }

    public function productsAll(Request $request)
    {
        return Product::all();
    }

    public function duplicateProduct(Request $request)
    {
        if ($request->category_id == 0) $request->merge(['category_id' => null]);
        if ($request->agencia_id == 0) $request->merge(['agencia_id' => null]);
        $request->merge(['cantidadAlmacen' => $request->cantidad]);
        return Product::create($request->all());
    }

    public function index()
    {
        error_log('lorem ipsum dolor sit amet');
        $search = request()->input('search', '');
        $search = strtoupper($search);
        $search = str_replace(' ', '%', $search);
        $search = $search==null || $search=='' ? '%' : '%'.$search.'%';
        $ordenar = request()->input('order', 'id');
        $category_id = request()->input('category', 0);
        $agencia_id = request()->input('agencia', 0);
        $paginate = request()->input('paginate', 30);
        $sub_category_id = request()->input('subcategory', 0);

        $query = Product::query();

        $query->where(function ($query) use ($search) {
            $query->where('nombre', 'like', "%$search%")
                ->orWhere('composicion', 'like', "%$search%")
                ->orWhere('marca', 'like', "%$search%")
                ->orWhere('distribuidora', 'like', "%$search%")
                ->orWhere('paisOrigen', 'like', "%$search%");
        });

        if ($category_id != 0) {
            $query->where('category_id', $category_id);
        }
        if ($sub_category_id != 0) {
            $query->where('subcategory_id', $sub_category_id);
        }

        if ($agencia_id != 0) {
            $query->where("cantidadSucursal$agencia_id", '>', 0);
        }

        if ($ordenar == 'id') {
            $ordenarRaw = 'id desc';
        } else if ($ordenar == 'precio asc') {
            $ordenarRaw = 'precio asc';
        } else if ($ordenar == 'precio desc') {
            $ordenarRaw = 'precio desc';
        } else if ($ordenar == 'cantidad asc') {
            $sucursal = $agencia_id == 0 ? 'cantidadAlmacen' : "cantidadSucursal$agencia_id";
            $ordenarRaw = "$sucursal asc";
        } else if ($ordenar == 'cantidad desc') {
            $sucursal = $agencia_id == 0 ? 'cantidadAlmacen' : "cantidadSucursal$agencia_id";
            $ordenarRaw = "$sucursal desc";
        } else if ($ordenar == 'nombre asc') {
            $ordenarRaw = "nombre asc";
        } else {
            $ordenarRaw = "id desc";
        }
        error_log('orderRaw: '.$ordenarRaw);

        $products = $query->orderByRaw($ordenarRaw)
            ->with(['category', 'agencia'])
            ->paginate($paginate);

        $costoTotal = $query->select(DB::raw('sum(costo*cantidad)'))
            ->groupBy('agencia_id')
            ->first();

        $costoTotal = $costoTotal ? $costoTotal->{"sum(costo*cantidad)"} : 0;
        $products->each(function ($product) use ($agencia_id) {
            if ($agencia_id != 0) {
                $product->cantidad = $product->{"cantidadSucursal$agencia_id"};
            }
            if (!file_exists(public_path() . '/images/' . $product->imagen)) {
                $product->imagen = 'productDefault.jpg';
            }
        });

        return response()->json(['products' => $products, 'costoTotal' => $costoTotal]);
    }

    public function productsSale(Request $request)
    {
        $search = request()->input('search', '');
        $search = strtoupper($search);
        $search = str_replace(' ', '%', $search);
        $search = $search==null || $search=='' ? '%' : '%'.$search.'%';
        $ordenar = request()->input('order', 'id');
        $category_id = request()->input('category', 0);
        $sub_category_id = request()->input('subcategory', 0);
        $agencia_id = request()->input('agencia', 0);
        $paginate = request()->input('paginate', 30);

        $query = Product::query();
        $query->where('nombre', 'like', "%$search%");
        $query->with(['buys' => function ($query) {
            $query->orderBy('created_at', 'desc')
                ->where('cantidadVendida', '>', 0)
                ->limit(7);
        }]);

        if ($category_id != 0) {
            $query->where('category_id', $category_id);
        }
        if ($sub_category_id != 0) {
            $query->where('subcategory_id', $sub_category_id);
        }

        if ($agencia_id != 0) {
            $query->where("cantidadSucursal$agencia_id", '>=', 0);
        }

        if ($ordenar == 'id') {
            $ordenarRaw = 'id desc';
        } else if ($ordenar == 'precio asc') {
            $ordenarRaw = 'precio asc';
        } else if ($ordenar == 'precio desc') {
            $ordenarRaw = 'precio desc';
        } else if ($ordenar == 'cantidad asc') {
            $sucursal = $agencia_id == 0 ? 'cantidadAlmacen' : "cantidadSucursal$agencia_id";
            $ordenarRaw = "$sucursal asc";
        } else if ($ordenar == 'cantidad desc') {
            $sucursal = $agencia_id == 0 ? 'cantidadAlmacen' : "cantidadSucursal$agencia_id";
            $ordenarRaw = "$sucursal desc";
        } else if ($ordenar == 'nombre asc') {
            $ordenarRaw = "nombre asc";
        } else {
            $ordenarRaw = "id desc";
        }
        error_log('orderRaw: '.$ordenarRaw);

        $products = $query->orderByRaw($ordenarRaw)
            ->with(['category', 'agencia'])
            ->paginate($paginate);

        $costoTotal = $query->select(DB::raw('sum(costo*cantidad)'))
            ->groupBy('agencia_id')
            ->first();

        $costoTotal = $costoTotal ? $costoTotal->{"sum(costo*cantidad)"} : 0;
        $products->each(function ($product) use ($agencia_id) {
            if ($agencia_id != 0) {
                $product->cantidad = $product->{"cantidadSucursal$agencia_id"};
            }
            if (!file_exists(public_path() . '/images/' . $product->imagen)) {
                $product->imagen = 'productDefault.jpg';
            }
        });

        return response()->json(['products' => $products, 'costoTotal' => $costoTotal]);
    }

    public function store(StoreProductRequest $request)
    {
        if ($request->category_id == 0) $request->merge(['category_id' => null]);
        $request->merge(['cantidadAlmacen' => $request->cantidad]);
        $request->merge(['nombre' => strtoupper($request->nombre)]);
        $request->merge(['paisOrigen' => strtoupper($request->paisOrigen)]);
        $request->merge(['marca' => strtoupper($request->marca)]);
        $request->merge(['distribuidora' => strtoupper($request->distribuidora)]);
        $request->merge(['agencia_id' => null]);

        // 🔧 Normaliza activo/en_oferta
        $this->normalizeActivoAndOferta($request);

        return Product::create($request->all());
    }

    public function show(Product $product)
    {
        return $product;
    }

    public function moverProducto(Request $request)
    {
        $product = Product::find($request->id);
        $delSucursal='cantidadSucursal'.$request->delSucursal;
        $agencias = Agencia::all();
        $cantidad = $request->cantidad;
        $lugar = $request->lugar;
        if ($request->lugar == 'Almacen'){
            $product->$delSucursal = $product->$delSucursal - $cantidad;
            $product->cantidadAlmacen = $product->cantidadAlmacen + $cantidad;
            $product->save();
            $this->transferHistoryCreate($request->delSucursal, null, $request->id, $cantidad,$request->fecha_entrega_vencimiento, $request);
        }else{
            $product->$delSucursal = $product->$delSucursal - $cantidad;
            $product->save();
            foreach ($agencias as $agencia){
                if ($agencia['nombre'] == $lugar){
                    $product->{"cantidadSucursal".$agencia['id']} = $product->{"cantidadSucursal".$agencia['id']} + $cantidad;
                    $product->save();

                    $this->transferHistoryCreate($request->delSucursal, $agencia['id'], $request->id, $cantidad,$request->fecha_entrega_vencimiento, $request);

                    return $product;
                }
            }
        }
        return $product;
    }

    public function transferenciasMultiples(Request $request)
    {
        $productos = $request->productos;
        $agencia_origen = $request->agencia_origen_id;
        $agencia_destino = $request->agencia_destino_id;

        foreach ($productos as $item) {
            $producto = Product::find($item['id']);
            $cantidad = $item['cantidad'];
            $fecha = $item['fechaVencimiento'] ?? null;
            $origen = $item['origen'] ?? 'sucursal';

            if ($origen === 'almacen') {
                if ($producto->cantidadAlmacen < $cantidad) {
                    return response()->json([
                        'message' => "No hay suficiente stock en almacén para el producto: " . $producto->nombre
                    ], 400);
                }

                $producto->cantidadAlmacen -= $cantidad;
                $campo_destino = 'cantidadSucursal' . $agencia_destino;
                $producto->$campo_destino += $cantidad;

                $this->transferHistoryCreate(null, $agencia_destino, $producto->id, $cantidad, $fecha, $request);
            } else {
                $campo_origen = 'cantidadSucursal' . $agencia_origen;
                $campo_destino = 'cantidadSucursal' . $agencia_destino;

                if ($producto->$campo_origen < $cantidad) {
                    return response()->json([
                        'message' => "No hay suficiente stock en la sucursal origen para el producto: " . $producto->nombre
                    ], 400);
                }

                $producto->$campo_origen -= $cantidad;
                $producto->$campo_destino += $cantidad;

                $this->transferHistoryCreate($agencia_origen, $agencia_destino, $producto->id, $cantidad, $fecha, $request);
            }

            $producto->save();
        }

        $origen = Agencia::find($agencia_origen);
        $origenNombre = $origen ? $origen->nombre : 'Sucursal desconocida';

        $mensaje = "Has recibido una transferencia de productos desde la agencia: $origenNombre.";

        Notificacion::create([
            'agencia_id' => $agencia_destino,
            'mensaje' => $mensaje,
            'detalle' => json_encode($productos),
            'leida' => false
        ]);

        return response()->json(['message' => 'Transferencia múltiple exitosa']);
    }

    public  function agregarSucursal(Request $request)
    {
        $product = Product::find($request->id);
        $sucursal = 'cantidadSucursal'.$request->sucursal;
        $product->$sucursal = $product->$sucursal + $request->cantidad;
        $product->cantidadAlmacen = $product->cantidadAlmacen - $request->cantidad;
        $product->save();
        $this->transferHistoryCreate(null, $request->sucursal, $request->id, $request->cantidad,$request->fecha_entrega_vencimiento, $request);
        return $product;
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $request->merge(['nombre' => strtoupper($request->nombre)]);
        $request->merge(['paisOrigen' => strtoupper($request->paisOrigen)]);
        $request->merge(['marca' => strtoupper($request->marca)]);
        $request->merge(['distribuidora' => strtoupper($request->distribuidora)]);
        $request->merge(['cantidad' => $request->cantidad]);

        // 🔧 Normaliza activo/en_oferta (si viene activo=INACTIVO, en_oferta debe ir false)
        $this->normalizeActivoAndOferta($request);

        return $product->update($request->all());
    }

    public function destroy(Product $product)
    {
        return $product->delete();
    }

    public function transferHistoryCreate($agencia_id_origen, $agencia_id_destino, $product_id, $cantidad,$fecha_entrega_vencimiento, Request $request)
    {
        $fecha_entrega_vencimiento = $fecha_entrega_vencimiento=='' || $fecha_entrega_vencimiento=='null' ? null : $fecha_entrega_vencimiento;
        $transferHistory = new TransferHistory();
        $transferHistory->user_id = $request->user()->id;
        $transferHistory->agencia_id_origen = $agencia_id_origen;
        $transferHistory->agencia_id_destino = $agencia_id_destino;
        $transferHistory->fecha_entrega_vencimiento = $fecha_entrega_vencimiento;
        $transferHistory->producto_id = $product_id;
        $transferHistory->cantidad = $cantidad;
        $transferHistory->fecha = date('Y-m-d');
        $transferHistory->hora = date('H:i:s');
        $transferHistory->save();
    }

    // ======================================================
    // ============  AUTOCOMPLETADO / SUGERENCIAS  ==========
    // ======================================================
    public function suggest(Request $request)
    {
        $term = trim((string) $request->get('q', ''));
        if ($term === '') {
            return response()->json([]);
        }

        $agenciaId = (int) $request->input('agencia_id', 0);
        $limit     = min(max((int) $request->input('limit', 10), 1), 20);
        $inStock   = (int) $request->input('in_stock', 0) === 1;

        $safeUpper   = mb_strtoupper($term, 'UTF-8');
        $likeContain = "%{$safeUpper}%";
        $likeStart   = "{$safeUpper}%";

        $select = ['id', 'nombre', 'imagen', 'precio'];
        if (Schema::hasColumn('products', 'porcentaje'))    { $select[] = 'porcentaje'; }
        if (Schema::hasColumn('products', 'precioAntes'))   { $select[] = 'precioAntes'; }
        if (Schema::hasColumn('products', 'precioNormal'))  { $select[] = 'precioNormal'; }
        if (Schema::hasColumn('products', 'en_oferta'))     { $select[] = 'en_oferta'; }

        if ($inStock) {
            if ($agenciaId > 0) {
                $col = 'cantidadSucursal' . $agenciaId;
                if (Schema::hasColumn('products', $col)) { $select[] = $col; }
            } else {
                if (Schema::hasColumn('products', 'cantidadAlmacen')) { $select[] = 'cantidadAlmacen'; }
                elseif (Schema::hasColumn('products', 'cantidad'))    { $select[] = 'cantidad'; }
            }
        }

        $q = Product::query()->select($select);

        $q->where(function ($qq) use ($likeContain) {
            $qq->whereRaw('UPPER(nombre) LIKE ?', [$likeContain]);
            if (Schema::hasColumn('products', 'composicion'))  { $qq->orWhereRaw('UPPER(composicion) LIKE ?',  [$likeContain]); }
            if (Schema::hasColumn('products', 'marca'))        { $qq->orWhereRaw('UPPER(marca) LIKE ?',        [$likeContain]); }
            if (Schema::hasColumn('products', 'distribuidora')){ $qq->orWhereRaw('UPPER(distribuidora) LIKE ?',[$likeContain]); }
            if (Schema::hasColumn('products', 'sku'))          { $qq->orWhereRaw('UPPER(sku) LIKE ?',          [$likeContain]); }
        });

        if (Schema::hasColumn('products', 'activo')) {
            $q->where(function ($qq) {
                $qq->where('activo', 'ACTIVO')
                   ->orWhere('activo', 1)
                   ->orWhere('activo', '1')
                   ->orWhere('activo', true)
                   ->orWhereIn('activo', ['true', 'TRUE']);
            });
        }

        if ($inStock) {
            if ($agenciaId > 0) {
                $col = 'cantidadSucursal' . $agenciaId;
                if (Schema::hasColumn('products', $col)) { $q->where($col, '>', 0); }
            } else {
                if (Schema::hasColumn('products', 'cantidadAlmacen')) { $q->where('cantidadAlmacen', '>', 0); }
                elseif (Schema::hasColumn('products', 'cantidad'))    { $q->where('cantidad', '>', 0); }
            }
        }

        $orderSql = 'CASE 
            WHEN UPPER(nombre) LIKE ? THEN 0
            WHEN UPPER(nombre) LIKE ? THEN 1
            ELSE 2
        END';

        $items = $q->orderByRaw($orderSql, [$likeStart, $likeContain])
                   ->orderBy('nombre')
                   ->limit($limit)
                   ->get();

        $payload = $items->map(function ($p) use ($agenciaId) {
            $img = $p->imagen;
            if (empty($img) || !file_exists(public_path().'/images/'.$img)) {
                $img = 'productDefault.jpg';
            }

            $stock = 0;
            if ($agenciaId > 0) {
                $col = 'cantidadSucursal' . $agenciaId;
                $stock = isset($p->$col) ? (int) $p->$col : 0;
            } else {
                if (isset($p->cantidadAlmacen))      { $stock = (int) $p->cantidadAlmacen; }
                elseif (isset($p->cantidad))         { $stock = (int) $p->cantidad; }
                else                                  { $stock = 0; }
            }

            $precioBase  = (float) ($p->precio ?? 0);
            $porcentaje  = (float) ($p->porcentaje ?? 0);

            $antesRaw = $p->precioAntes ?? $p->precioNormal ?? null;
            $precioAntes = (isset($antesRaw) && $antesRaw !== '' && is_numeric($antesRaw))
                ? (float) $antesRaw : null;

            $precio_antes = null;
            $precio_ahora = $precioBase;

            if ($porcentaje > 0) {
                $baseAntes   = ($precioAntes && $precioAntes > 0) ? $precioAntes : $precioBase;
                $precio_antes = $baseAntes;
                $precio_ahora = $baseAntes * (1 - $porcentaje / 100);
            } else {
                if ($precioAntes && $precioAntes > 0) {
                    $precio_antes = $precioAntes;
                    $precio_ahora = $precioBase;
                }
            }

            if (($porcentaje === 0 || $porcentaje === null) && $precio_antes && $precio_ahora) {
                $diff = $precio_antes - $precio_ahora;
                $porcentaje = ($precio_antes > 0) ? round(($diff / $precio_antes) * 100) : 0;
            }

            $precio_sin_descuento = $precio_antes ?? $precioBase;
            $precio_con_descuento = $precio_ahora;

            return [
                'id'     => $p->id,
                'title'  => $p->nombre,
                'imagen' => $img,
                'precio'        => (float) $precioBase,
                'precio_antes'  => $precio_antes ? round($precio_antes, 2) : null,
                'precio_ahora'  => round($precio_ahora, 2),
                'porcentaje'    => (int) $porcentaje,
                'precio_sin_descuento' => round($precio_sin_descuento, 2),
                'precio_con_descuento' => round($precio_con_descuento, 2),
                'stock'         => $stock,
            ];
        });

        return response()->json($payload);
    }

    // ===== Helpers =====
    private function normalizeActivoAndOferta(Request $request): void
    {
        if ($request->has('activo')) {
            $val = $request->input('activo');
            $on = false;

            if (is_bool($val)) {
                $on = $val;
            } elseif (is_numeric($val)) {
                $on = ((int) $val) === 1;
            } else {
                $up = strtoupper((string) $val);
                $on = in_array($up, ['ACTIVO', 'TRUE', 'ON', 'YES', '1'], true);
            }

            $request->merge(['activo' => $on ? 'ACTIVO' : 'INACTIVO']);

            // Si está inactivo, forzamos en_oferta a false
            if (!$on) {
                $request->merge(['en_oferta' => false]);
            }
        }
    }
}
