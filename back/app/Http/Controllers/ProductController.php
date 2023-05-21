<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller{
    public function index(){
        $search = request()->get('search', '');
        $search = $search == 'null' ? '' : $search;
        $ordenar = request()->get('order', 'id');
        $category_id = request()->get('category', 'id');
        $paginate = request()->get('paginate', 60);
        if ($category_id == 0){
            $products = Product::where('nombre', 'ilike', "%$search%")->orderByRaw($ordenar)->paginate($paginate);
            $costoRow=Product::select(DB::raw('sum(costo) as costoTotal'))->where('nombre', 'ilike', "%$search%")->first();
            $costoTotal=$costoRow->costototal==null?0:$costoRow->costototal;
        }else{
            $products = Product::where('nombre', 'ilike', "%$search%")->where('category_id', $category_id)->orderByRaw($ordenar)->paginate($paginate);
            $costoRow=Product::select(DB::raw('sum(costo) as costoTotal'))->where('nombre', 'ilike', "%$search%")->where('category_id', $category_id)->first();
            $costoTotal=$costoRow->costototal==null?0:$costoRow->costototal;
        }
        return json_encode(['products' => $products, 'costoTotal' => $costoTotal]);
    }
    public function store(StoreProductRequest $request){
        if ($request->category_id == 0) $request->merge(['category_id' => null]);
        return Product::create($request->all());
    }
    public function show(Product $product){ return $product; }
    public function update(UpdateProductRequest $request, Product $product){ return $product->update($request->all()); }
    public function destroy(Product $product){ return $product->delete(); }
}
