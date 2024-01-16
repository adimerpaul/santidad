<?php

namespace App\Http\Controllers;

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
    public function index(){
        $search = request()->get('search', '');
        $search = $search == 'null' ? '' : $search;
        $ordenar = request()->get('order', 'id');
        $category_id = request()->get('category', 'id');
        $agencia_id = request()->get('agencia', 'id');
//        if ($agencia_id== 'null' || $agencia_id == 'undefined') $agencia_id = 0;
//        if ($category_id== 'null' || $category_id == 'undefined') $category_id = 0;
        $paginate = request()->get('paginate', 30);
        if ($category_id == 0 && $agencia_id == 0){
            $products = Product::where('nombre', 'like', "%$search%")
                ->orderByRaw($ordenar)
                ->with(['category', 'agencia'])
                ->paginate($paginate);
            $costoRow=Product::select(DB::raw('sum(costo*cantidad)'))
                ->where('nombre', 'like', "%$search%")
                ->first();
            $costoTotal=$costoRow["sum(costo*cantidad)"]==null?0:$costoRow["sum(costo*cantidad)"];
        }else{
            if ($category_id == 0 && $agencia_id != 0){
                $products = Product::where('nombre', 'like', "%$search%")
                    ->where('agencia_id', $agencia_id)
                    ->orderByRaw($ordenar)
                    ->with(['category', 'agencia'])
                    ->paginate($paginate);
                $costoRow=Product::select(DB::raw('sum(costo*cantidad)'))
                    ->where('nombre', 'like', "%$search%")
                    ->where('agencia_id', $agencia_id)
                    ->first();
                $costoTotal=$costoRow["sum(costo*cantidad)"]==null?0:$costoRow["sum(costo*cantidad)"];
            }else if ($category_id != 0 && $agencia_id == 0){
                $products = Product::where('nombre', 'like', "%$search%")
                    ->where('category_id', $category_id)
                    ->orderByRaw($ordenar)
                    ->with(['category', 'agencia'])
                    ->paginate($paginate);
                $costoRow=Product::select(DB::raw('sum(costo*cantidad)'))
                    ->where('nombre', 'like', "%$search%")
                    ->where('category_id', $category_id)
                    ->first();
                $costoTotal=$costoRow["sum(costo*cantidad)"]==null?0:$costoRow["sum(costo*cantidad)"];
            }else if ($category_id != 0 && $agencia_id != 0){
                $products = Product::where('nombre', 'like', "%$search%")
                    ->where('category_id', $category_id)
                    ->where('agencia_id', $agencia_id)
                    ->orderByRaw($ordenar)
                    ->with(['category', 'agencia'])
                    ->paginate($paginate);
                $costoRow=Product::select(DB::raw('sum(costo*cantidad)'))
                    ->where('nombre', 'like', "%$search%")
                    ->where('category_id', $category_id)
                    ->where('agencia_id', $agencia_id)
                    ->first();
                $costoTotal=$costoRow["sum(costo*cantidad)"]==null?0:$costoRow["sum(costo*cantidad)"];
            }
//            $products = Product::where('nombre', 'like', "%$search%")
//                ->where('category_id', $category_id)
//                ->orderByRaw($ordenar)
//                ->with('category')
//                ->paginate($paginate);
//            $costoRow=Product::select(DB::raw('sum(costo*cantidad)'))
//                ->where('nombre', 'like', "%$search%")
//                ->where('category_id', $category_id)
//                ->first();
//            $costoTotal=$costoRow["sum(costo*cantidad)"]==null?0:$costoRow["sum(costo*cantidad)"];
        }
        return json_encode(['products' => $products, 'costoTotal' => $costoTotal]);
    }
    public function productsSale(Request $request){
        $search = request()->get('search', '');
        $search = $search == 'null' ? '' : $search;
        $ordenar = request()->get('order', 'id');
        $category_id = request()->get('category', 'id');
        $agencia_id = request()->get('agencia', 'id');
//        if ($agencia_id== 'null' || $agencia_id == 'undefined') $agencia_id = 0;
//        if ($category_id== 'null' || $category_id == 'undefined') $category_id = 0;
        $numeroAgencia = $request->user()->agencia_id;
        $paginate = request()->get('paginate', 30);
        if ($category_id == 0 && $agencia_id == 0){
//            $products = Product::where('nombre', 'like', "%$search%")
//                ->orderByRaw($ordenar)
//                ->with(['category', 'agencia'])
//                ->paginate($paginate);
//            $costoRow=Product::select(DB::raw('sum(costo*cantidad)'))
//                ->where('nombre', 'like', "%$search%")
//                ->first();
//            $costoTotal=$costoRow["sum(costo*cantidad)"]==null?0:$costoRow["sum(costo*cantidad)"];
        }else{
            if ($category_id == 0 && $agencia_id != 0){
                $products = Product::where('nombre', 'like', "%$search%")
                    ->where('agencia_id', $agencia_id)
                    ->orderByRaw($ordenar)
                    ->with(['category', 'agencia'])
                    ->where('cantidadSucursal'.$numeroAgencia,'>',0)
                    ->paginate($paginate);
                $costoRow=Product::select(DB::raw('sum(costo*cantidad)'))
                    ->where('nombre', 'like', "%$search%")
                    ->where('agencia_id', $agencia_id)
                    ->first();
                $costoTotal=$costoRow["sum(costo*cantidad)"]==null?0:$costoRow["sum(costo*cantidad)"];
            }
//            else if ($category_id != 0 && $agencia_id == 0){
//                $products = Product::where('nombre', 'like', "%$search%")
//                    ->where('category_id', $category_id)
//                    ->orderByRaw($ordenar)
//                    ->with(['category', 'agencia'])
//                    ->paginate($paginate);
//                $costoRow=Product::select(DB::raw('sum(costo*cantidad)'))
//                    ->where('nombre', 'like', "%$search%")
//                    ->where('category_id', $category_id)
//                    ->first();
//                $costoTotal=$costoRow["sum(costo*cantidad)"]==null?0:$costoRow["sum(costo*cantidad)"];
//            }
            else if ($category_id != 0 && $agencia_id != 0){
                $products = Product::where('nombre', 'like', "%$search%")
                    ->where('category_id', $category_id)
                    ->where('agencia_id', $agencia_id)
                    ->orderByRaw($ordenar)
                    ->with(['category', 'agencia'])
                    ->paginate($paginate);
                $costoRow=Product::select(DB::raw('sum(costo*cantidad)'))
                    ->where('nombre', 'like', "%$search%")
                    ->where('category_id', $category_id)
                    ->where('agencia_id', $agencia_id)
                    ->first();
                $costoTotal=$costoRow["sum(costo*cantidad)"]==null?0:$costoRow["sum(costo*cantidad)"];
            }
//            $products = Product::where('nombre', 'like', "%$search%")
//                ->where('category_id', $category_id)
//                ->orderByRaw($ordenar)
//                ->with('category')
//                ->paginate($paginate);
//            $costoRow=Product::select(DB::raw('sum(costo*cantidad)'))
//                ->where('nombre', 'like', "%$search%")
//                ->where('category_id', $category_id)
//                ->first();
//            $costoTotal=$costoRow["sum(costo*cantidad)"]==null?0:$costoRow["sum(costo*cantidad)"];
        }
        return json_encode(['products' => $products, 'costoTotal' => $costoTotal]);
    }
    public function store(StoreProductRequest $request){
        if ($request->category_id == 0) $request->merge(['category_id' => null]);
        if ($request->agencia_id == 0) $request->merge(['agencia_id' => null]);
        $request->merge(['cantidadAlmacen' => $request->cantidad]);
        return Product::create($request->all());
    }
    public function show(Product $product){ return $product; }

    public function moverProducto(Request $request){

        $product = Product::find($request->id);
        $delSucursal='cantidadSucursal'.$request->delSucursal;
        if ($request->lugar == 'Almacen'){
            $product->$delSucursal = $product->$delSucursal - $request->cantidad;
            $product->cantidadAlmacen = $product->cantidadAlmacen + $request->cantidad;
            $product->save();
        }else if ($request->lugar == 'Sucursal 1'){
            $product->$delSucursal = $product->$delSucursal - $request->cantidad;
            $product->cantidadSucursal1 = $product->cantidadSucursal1 + $request->cantidad;
            $product->save();
        }else if ($request->lugar == 'Sucursal 2'){
            $product->$delSucursal = $product->$delSucursal - $request->cantidad;
            $product->cantidadSucursal2 = $product->cantidadSucursal2 + $request->cantidad;
            $product->save();
        }else if ($request->lugar == 'Sucursal 3'){
            $product->$delSucursal = $product->$delSucursal - $request->cantidad;
            $product->cantidadSucursal3 = $product->cantidadSucursal3 + $request->cantidad;
            $product->save();
        }else if ($request->lugar == 'Sucursal 4'){
            $product->$delSucursal = $product->$delSucursal - $request->cantidad;
            $product->cantidadSucursal4 = $product->cantidadSucursal4 + $request->cantidad;
            $product->save();
        }
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
        $request->merge(['cantidadAlmacen' => $request->cantidad]);
        return $product->update($request->all());
    }
    public function destroy(Product $product){ return $product->delete(); }
}
