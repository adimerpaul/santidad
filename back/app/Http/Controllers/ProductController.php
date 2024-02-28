<?php

namespace App\Http\Controllers;

use App\Models\Agencia;
use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller{
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
        $search = request()->input('search', '');
        $search = strtoupper($search);
        $search = str_replace(' ', '%', $search);
        $search = $search==null || $search=='' ? '%' : '%'.$search.'%';
        $ordenar = request()->input('order', 'id');
        $category_id = request()->input('category', 0);
        $agencia_id = request()->input('agencia', 0);
        $paginate = request()->input('paginate', 30);

        $query = Product::query();
        $query->where('nombre', 'like', "%$search%");

        if ($category_id != 0) {
            $query->where('category_id', $category_id);
        }

        if ($agencia_id != 0) {
            $query->where("cantidadSucursal$agencia_id", '>', 0);
        }

        $products = $query->orderByRaw($ordenar)
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
        $search = $request->input('search', '');
        $ordenar = $request->input('order', 'id');
        $category_id = $request->input('category', 0);
        $agencia_id = $request->input('agencia', 0);
        $numeroAgencia = $request->user()->agencia_id;
        $paginate = $request->input('paginate', 30);

        $query = Product::where('nombre', 'like', "%$search%")
            ->orderByRaw($ordenar)
            ->with(['category', 'agencia']);

        if ($category_id != 0) {
            $query->where('category_id', $category_id);
        }

        if ($agencia_id != 0) {
//            $query->where('agencia_id', $agencia_id);
            // Assuming 'cantidadSucursal' is a dynamic field based on $numeroAgencia
            $query->where("cantidadSucursal$numeroAgencia", '>', 0);
        }

        $products = $query->paginate($paginate);

        $costoTotal = $query->select(DB::raw('sum(costo*cantidad)'))
            ->groupBy('agencia_id') // Add this line to fix the issue
            ->first();

        $costoTotal = $costoTotal ? $costoTotal->{"sum(costo*cantidad)"} : 0;

        $products->each(function ($product) {
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
        }else{
            $product->$delSucursal = $product->$delSucursal - $cantidad;
            $product->save();
            foreach ($agencias as $agencia){
                if ($agencia['nombre'] == $lugar){
                    $product->{"cantidadSucursal".$agencia['id']} = $product->{"cantidadSucursal".$agencia['id']} + $cantidad;
                    $product->save();
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
    public  function agregarSucursal(Request $request){
        $product = Product::find($request->id);
        $sucursal = 'cantidadSucursal'.$request->sucursal;
        $product->$sucursal = $product->$sucursal + $request->cantidad;
        $product->cantidadAlmacen = $product->cantidadAlmacen - $request->cantidad;
        $product->save();
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
}
