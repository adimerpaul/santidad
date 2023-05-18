<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;

class ProductController extends Controller{
    public function index(){ return Product::all(); }
    public function store(StoreProductRequest $request){
        if ($request->category_id == 0) $request->merge(['category_id' => null]);
        return Product::create($request->all());
    }
    public function show(Product $product){ return $product; }
    public function update(UpdateProductRequest $request, Product $product){ return $product->update($request->all()); }
    public function destroy(Product $product){ return $product->delete(); }
}
