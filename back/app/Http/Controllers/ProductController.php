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
    public function verificarStockSucursal(Request $request)
{
    $request->validate([
        'sucursal_id' => 'required|exists:agencias,id',
        'productos' => 'required|array',
        'productos.*.producto_id' => 'required|exists:products,id',
        'productos.*.cantidad' => 'required|integer|min:1'
    ]);

    $sucursal_id = $request->sucursal_id;
    $productos = $request->productos;

    $productosSinStock = [];

    foreach ($productos as $producto) {
        $productoModel = Product::find($producto['producto_id']);
        if (!$productoModel) {
            continue;
        }

        // Usar la misma lógica que en verificarStockVenta
        $campoCantidad = 'cantidadSucursal' . $sucursal_id;
        $stockDisponible = $productoModel->$campoCantidad ?? 0;

        if ($producto['cantidad'] > $stockDisponible) {
            $productosSinStock[] = [
                'producto_id' => $producto['producto_id'],
                'nombre' => $productoModel->nombre,
                'cantidad_solicitada' => $producto['cantidad'],
                'stock_disponible' => $stockDisponible
            ];
        }
    }

    return response()->json([
        'sucursal' => ['id' => $sucursal_id],
        'productos_sin_stock' => $productosSinStock
    ]);
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
        $distribuidora = request()->input('distribuidora');

        $query = Product::query();

        $query->where(function ($query) use ($search) {
            $query->where('nombre', 'like', "%$search%")
                ->orWhere('composicion', 'like', "%$search%");
        });

        if ($category_id != 0) {
            $query->where('category_id', $category_id);
        }
        if ($sub_category_id != 0) {
            $query->where('subcategory_id', $sub_category_id);
        }

        if ($agencia_id != 0) {
            $query->where("cantidadSucursal$agencia_id", '>=', 0);
        }
        if ($distribuidora && $distribuidora != '' && $distribuidora != 'null') {
             // Los signos % significan "cualquier cosa antes o después"
             $query->where('distribuidora', 'LIKE', '%' . $distribuidora . '%');
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

//    public function productsSale(Request $request)
//    {
//        $search = request()->input('search', '');
//        $search = strtoupper($search);
//        $search = str_replace(' ', '%', $search);
//        $search = $search==null || $search=='' ? '%' : '%'.$search.'%';
//        $ordenar = request()->input('order', 'id');
//        $category_id = request()->input('category', 0);
//        $sub_category_id = request()->input('subcategory', 0);
//        $agencia_id = request()->input('agencia', 0);
//        $paginate = request()->input('paginate', 30);
//
//        $query = Product::query();
//        $query->where('nombre', 'like', "%$search%");
//        $query->with(['buys' => function ($query) {
//            $query->orderBy('created_at', 'desc')
//                ->where('cantidadVendida', '>', 0)
//                ->limit(7);
//        }]);
//
//        if ($category_id != 0) {
//            $query->where('category_id', $category_id);
//        }
//        if ($sub_category_id != 0) {
//            $query->where('subcategory_id', $sub_category_id);
//        }
//
//        if ($agencia_id != 0) {
//            $query->where("cantidadSucursal$agencia_id", '>=', 0);
//        }
//
//        if ($ordenar == 'id') {
//            $ordenarRaw = 'id desc';
//        } else if ($ordenar == 'precio asc') {
//            $ordenarRaw = 'precio asc';
//        } else if ($ordenar == 'precio desc') {
//            $ordenarRaw = 'precio desc';
//        } else if ($ordenar == 'cantidad asc') {
//            $sucursal = $agencia_id == 0 ? 'cantidadAlmacen' : "cantidadSucursal$agencia_id";
//            $ordenarRaw = "$sucursal asc";
//        } else if ($ordenar == 'cantidad desc') {
//            $sucursal = $agencia_id == 0 ? 'cantidadAlmacen' : "cantidadSucursal$agencia_id";
//            $ordenarRaw = "$sucursal desc";
//        } else if ($ordenar == 'nombre asc') {
//            $ordenarRaw = "nombre asc";
//        } else {
//            $ordenarRaw = "id desc";
//        }
//        error_log('orderRaw: '.$ordenarRaw);
//
//        $products = $query->orderByRaw($ordenarRaw)
//            ->with(['category', 'agencia'])
//            ->paginate($paginate);
//
//        $costoTotal = $query->select(DB::raw('sum(costo*cantidad)'))
//            ->groupBy('agencia_id')
//            ->first();
//
//        $costoTotal = $costoTotal ? $costoTotal->{"sum(costo*cantidad)"} : 0;
//        $products->each(function ($product) use ($agencia_id) {
//            if ($agencia_id != 0) {
//                $product->cantidad = $product->{"cantidadSucursal$agencia_id"};
//            }
//            if (!file_exists(public_path() . '/images/' . $product->imagen)) {
//                $product->imagen = 'productDefault.jpg';
//            }
//        });
//
//        return response()->json(['products' => $products, 'costoTotal' => $costoTotal]);
//    }
    public function productsSale(Request $request)
    {
        // 1) Sanitización y defaults
        $search         = strtoupper((string) $request->input('search', ''));
        $searchLike     = $search === '' ? '%' : '%' . str_replace(' ', '%', $search) . '%';
        $orderInput     = strtolower((string) $request->input('order', 'id desc')); 
        $category_id    = (int) $request->input('category', 0);
        $sub_category_id= (int) $request->input('subcategory', 0);
        $agencia_id     = (int) $request->input('agencia', 0);
        
        // --- NUEVO: Capturamos el proveedor ---
        $proveedor_id   = (int) $request->input('proveedor', 0);
        // --------------------------------------

        $paginate       = (int) $request->input('paginate', 30);
        $paginate       = max(10, min($paginate, 100)); 

        // 2) Resolver columna de cantidad por agencia
        $sucursalColumn = $agencia_id > 0 ? "cantidadSucursal{$agencia_id}" : "cantidadAlmacen";
        $cantidadColumnsWhitelist = array_merge(
            ['cantidadAlmacen'],
            array_map(fn($n) => "cantidadSucursal{$n}", range(1, 50))
        );
        if (!in_array($sucursalColumn, $cantidadColumnsWhitelist, true)) {
            $sucursalColumn = 'cantidadAlmacen';
        }

        // 3) Resolver orden seguro
        $allowedBase = ['id', 'precio', 'cantidad', 'nombre'];
        $parts = preg_split('/\s+/', trim($orderInput));
        $base  = $parts[0] ?? 'id';
        $dir   = strtolower($parts[1] ?? 'desc');
        $dir   = in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';

        if (!in_array($base, $allowedBase, true)) {
            $base = 'id';
        }

        $orderColumn = match ($base) {
            'cantidad' => $sucursalColumn,
            default    => $base,
        };

        // 4) Query base
        $query = Product::query()
            ->with([
                'buys' => function ($q) {
                    $q->where('cantidadVendida', '>', 0)
                        ->orderBy('created_at', 'desc')
                        ->limit(7);
                },
                'category', 'agencia'
            ])
            ->where('nombre', 'like', $searchLike);

        if ($category_id !== 0) {
            $query->where('category_id', $category_id);
        }
        if ($sub_category_id !== 0) {
            $query->where('subcategory_id', $sub_category_id);
        }
        if ($agencia_id !== 0) {
            $query->where($sucursalColumn, '>=', 0);
        }

        // --- LÓGICA DE FILTRO POR ÚLTIMO PROVEEDOR ---
        if ($proveedor_id !== 0) {
            // Buscamos products donde la subconsulta (la última compra) tenga este proveedor_id
            $query->whereRaw('
                (SELECT proveedor_id 
                 FROM buys 
                 WHERE buys.product_id = products.id 
                 ORDER BY created_at DESC, id DESC 
                 LIMIT 1) = ?
            ', [$proveedor_id]);
        }
        // ---------------------------------------------

        // 5) Orden seguro
        $query->orderBy($orderColumn, $dir);

        // 6) Paginación
        $products = $query->paginate($paginate);

        // 7) Costo total correcto (Aplicando los mismos filtros)
        $costoTotalRow = Product::query()
            ->when($category_id !== 0, fn($q) => $q->where('category_id', $category_id))
            ->when($sub_category_id !== 0, fn($q) => $q->where('subcategory_id', $sub_category_id))
            // Aplicamos el mismo filtro de proveedor al total
            ->when($proveedor_id !== 0, fn($q) => $q->whereRaw('
                (SELECT proveedor_id 
                 FROM buys 
                 WHERE buys.product_id = products.id 
                 ORDER BY created_at DESC, id DESC 
                 LIMIT 1) = ?
            ', [$proveedor_id]))
            ->where('nombre', 'like', $searchLike)
            ->selectRaw("SUM(costo * {$sucursalColumn}) as costo_total")
            ->first();

        $costoTotal = (float) ($costoTotalRow->costo_total ?? 0);

        // 8) Post-proceso de imagen
        $products->getCollection()->transform(function ($product) use ($sucursalColumn) {
            $product->cantidad = $product->{$sucursalColumn} ?? 0;

            if (empty($product->imagen) || !is_file(public_path('images/' . $product->imagen))) {
                $product->imagen = 'productDefault.jpg';
            }
            return $product;
        });

        return response()->json([
            'products'   => $products,
            'costoTotal' => $costoTotal,
            'order'      => "{$orderColumn} {$dir}",
        ]);
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
        // -----------------------------------------------------
        // 🛡️ BLOQUEO ANTI-DUPLICADOS (RACE CONDITION)
        // -----------------------------------------------------
        // 1. Calculamos el ID del destino para buscar en el historial
        $destinoId = null;
        if ($request->lugar != 'Almacen') {
            // Buscamos la agencia por nombre, ya que el frontend manda el nombre
            $agenciaDestino = Agencia::where('nombre', $request->lugar)->first();
            if ($agenciaDestino) $destinoId = $agenciaDestino->id;
        }

        // 2. Buscamos si YA existe este movimiento en los últimos 5 segundos
        $duplicado = TransferHistory::where('user_id', $request->user()->id)
            ->where('producto_id', $request->id)
            ->where('agencia_id_origen', $request->delSucursal) // ID del origen
            ->where('agencia_id_destino', $destinoId)           // ID del destino
            ->where('cantidad', $request->cantidad)
            ->where('created_at', '>', now()->subSeconds(5))    // <--- LA CLAVE
            ->first();

        if ($duplicado) {
            // Si ya existe, fingimos éxito devolviendo el producto sin hacer nada más
            return Product::find($request->id);
        }
        // -----------------------------------------------------

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
        
        if (empty($productos)) {
            return response()->json(['message' => 'No hay productos para transferir'], 400);
        }

        $agencia_origen = $request->agencia_origen_id;
        $agencia_destino = $request->agencia_destino_id;

        try {
            // ✅ SOLUCIÓN: Envolver todo el proceso en DB::transaction()
            DB::transaction(function () use ($productos, $agencia_origen, $agencia_destino, $request) {
                
                foreach ($productos as $item) {
                    $producto = Product::find($item['id']);
                    if (!$producto) {
                        throw new \Exception("El producto no existe.");
                    }

                    $cantidad = $item['cantidad'];
                    $fecha = $item['fechaVencimiento'] ?? null;
                    $origen = $item['origen'] ?? 'sucursal';

                    if ($origen === 'almacen') {
                        // Verificamos stock en almacén
                        if ($producto->cantidadAlmacen < $cantidad) {
                            // 🚨 Al lanzar la excepción, Laravel cancela TODO el foreach automáticamente
                            throw new \Exception("No hay suficiente stock en almacén para: " . $producto->nombre);
                        }
                        
                        $producto->cantidadAlmacen -= $cantidad;
                        $campo_destino = 'cantidadSucursal' . $agencia_destino;
                        $producto->$campo_destino += $cantidad;
                        
                        $this->transferHistoryCreate(null, $agencia_destino, $producto->id, $cantidad, $fecha, $request);
                    
                    } else {
                        $campo_origen = 'cantidadSucursal' . $agencia_origen;
                        $campo_destino = 'cantidadSucursal' . $agencia_destino;
                        
                        // Verificamos stock en sucursal
                        if ($producto->$campo_origen < $cantidad) {
                            // 🚨 Al lanzar la excepción, Laravel cancela TODO el foreach automáticamente
                            throw new \Exception("No hay suficiente stock en la sucursal origen para: " . $producto->nombre);
                        }
                        
                        $producto->$campo_origen -= $cantidad;
                        $producto->$campo_destino += $cantidad;
                        
                        $this->transferHistoryCreate($agencia_origen, $agencia_destino, $producto->id, $cantidad, $fecha, $request);
                    }
                    
                    // Guardamos el producto. Si más adelante otro producto falla, esto se revertirá.
                    $producto->save();
                }

                // 🔔 Si el ciclo terminó sin excepciones, creamos la notificación final
                $origenModel = Agencia::find($agencia_origen);
                $origenNombre = $origenModel ? $origenModel->nombre : 'Almacén Central';

                Notificacion::create([
                    'agencia_id' => $agencia_destino,
                    'agencia_origen_id' => $origenModel ? $origenModel->id : null,
                    'mensaje' => "Has recibido una transferencia de productos desde: $origenNombre.",
                    'detalle' => json_encode($productos),
                    'leida' => false
                ]);
            });

            // Si salimos de DB::transaction sin errores, respondemos éxito al frontend
            return response()->json(['message' => 'Transferencia múltiple exitosa']);

        } catch (\Exception $e) {
            // ❌ Capturamos el error (Ej: "No hay suficiente stock...") y lo enviamos al frontend
            // Laravel ya deshizo cualquier cambio en la base de datos en este punto.
            return response()->json(['message' => $e->getMessage()], 400);
        }
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
    public function getDistribuidoras()
    {
        // Obtiene las distribuidoras únicas, que no sean nulas ni vacías, ordenadas alfabéticamente
        $data = Product::select('distribuidora')
            ->whereNotNull('distribuidora')
            ->where('distribuidora', '!=', '')
            ->distinct()
            ->orderBy('distribuidora', 'ASC')
            ->get();

        return response()->json($data);
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
