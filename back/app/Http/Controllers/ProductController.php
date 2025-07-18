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


class ProductController extends Controller{
    public function verificarStock($id)
    {
        $producto = Product::find($id);

        if (!$producto) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        return response()->json([
            'stock' => $producto->cantidad
        ]);
    }
    public function productsAll(Request $request){
        return Product::all();
    }
    public function duplicateProduct(Request $request){
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
        //filtrar por nombre o comocicion
//        $query->where('nombre', 'like', "%$search%");
        $query->where(function ($query) use ($search) {
//            $query->where('nombre', 'like', "%$search%")
//                ->orWhere('composicion', 'like', "%$search%");
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

//        orders: [
//        { label: 'Ordenar por', value: 'id' },
//        { label: 'Menor precio', value: 'precio asc' },
//        { label: 'Mayor precio', value: 'precio desc' },
//        { label: 'Menor cantidad', value: 'cantidad asc' },
//        { label: 'Mayor cantidad', value: 'cantidad desc' },
//        { label: 'Orden alfabetico', value: 'nombre asc' }
//      ],
        if ($ordenar == 'id') {
            $ordenarRaw = 'id desc';
        } else if ($ordenar == 'precio asc') {
            $ordenarRaw = 'precio asc'; // ✅ Correcto
        } else if ($ordenar == 'precio desc') {
            $ordenarRaw = 'precio desc'; // ✅ Correcto
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
            $ordenarRaw = 'precio asc'; // ✅ Correcto
        } else if ($ordenar == 'precio desc') {
            $ordenarRaw = 'precio desc'; // ✅ Correcto
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
    public function store(StoreProductRequest $request){
        if ($request->category_id == 0) $request->merge(['category_id' => null]);
//        if ($request->agencia_id == 0) $request->merge(['agencia_id' => null]);
        $request->merge(['cantidadAlmacen' => $request->cantidad]);
        $request->merge(['cantidadAlmacen' => $request->cantidad]);
        $request->merge(['nombre' => strtoupper($request->nombre)]);
        $request->merge(['paisOrigen' => strtoupper($request->paisOrigen)]);
        $request->merge(['marca' => strtoupper($request->marca)]);
        $request->merge(['distribuidora' => strtoupper($request->distribuidora)]);
        $request->merge(['agencia_id' => null]);

        return Product::create($request->all());
    }
    public function show(Product $product){ return $product; }

    public function moverProducto(Request $request){

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


//        else if ($request->lugar == 'Sucursal 1'){
//            $product->$delSucursal = $product->$delSucursal - $request->cantidad;
//            $product->cantidadSucursal1 = $product->cantidadSucursal1 + $request->cantidad;
//            $product->save();
//        }else if ($request->lugar == 'Sucursal 2'){
//            $product->$delSucursal = $product->$delSucursal - $request->cantidad;
//            $product->cantidadSucursal2 = $product->cantidadSucursal2 + $request->cantidad;
//            $product->save();
//        }else if ($request->lugar == 'Sucursal 3'){
//            $product->$delSucursal = $product->$delSucursal - $request->cantidad;
//            $product->cantidadSucursal3 = $product->cantidadSucursal3 + $request->cantidad;
//            $product->save();
//        }else if ($request->lugar == 'Sucursal 4'){
//            $product->$delSucursal = $product->$delSucursal - $request->cantidad;
//            $product->cantidadSucursal4 = $product->cantidadSucursal4 + $request->cantidad;
//            $product->save();
//        }
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
        $origen = $item['origen'] ?? 'sucursal'; // puede ser 'almacen' o 'sucursal'

        if ($origen === 'almacen') {
            // Validar stock en almacén
            if ($producto->cantidadAlmacen < $cantidad) {
                return response()->json([
                    'message' => "No hay suficiente stock en almacén para el producto: " . $producto->nombre
                ], 400);
            }

            // Transferencia desde almacén
            $producto->cantidadAlmacen -= $cantidad;
            $campo_destino = 'cantidadSucursal' . $agencia_destino;
            $producto->$campo_destino += $cantidad;

            $this->transferHistoryCreate(null, $agencia_destino, $producto->id, $cantidad, $fecha, $request);
        } else {
            // Transferencia entre sucursales
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
    // Obtener nombre de la agencia origen
        $origen = Agencia::find($agencia_origen);
        $origenNombre = $origen ? $origen->nombre : 'Sucursal desconocida';

        // Armar mensaje
        $mensaje = "Has recibido una transferencia de productos desde la agencia: $origenNombre.";

        // Crear la notificación
        Notificacion::create([
            'agencia_id' => $agencia_destino,
            'mensaje' => $mensaje,
            'detalle' => json_encode($productos), // Puedes guardar el detalle como JSON
            'leida' => false
        ]);

    return response()->json(['message' => 'Transferencia múltiple exitosa']);
}

    public  function agregarSucursal(Request $request){
        $product = Product::find($request->id);
        $sucursal = 'cantidadSucursal'.$request->sucursal;
        $product->$sucursal = $product->$sucursal + $request->cantidad;
        $product->cantidadAlmacen = $product->cantidadAlmacen - $request->cantidad;
        $product->save();
        $this->transferHistoryCreate(null, $request->sucursal, $request->id, $request->cantidad,$request->fecha_entrega_vencimiento, $request);
        return $product;
    }
    public function update(UpdateProductRequest $request, Product $product){
        $request->merge(['nombre' => strtoupper($request->nombre)]);
        $request->merge(['paisOrigen' => strtoupper($request->paisOrigen)]);
        $request->merge(['marca' => strtoupper($request->marca)]);
        $request->merge(['distribuidora' => strtoupper($request->distribuidora)]);
        $request->merge(['cantidad' => $request->cantidad]);
        return $product->update($request->all());
    }
    public function destroy(Product $product){ return $product->delete(); }

    /**
     * @param Request $request
     * @param $id
     * @return void
     */
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
}
