<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;

class CategoryController extends Controller{
    public function index(){ return Category::all(); }
    public function store(StoreCategoryRequest $request){
        return Category::create($request->all());
    }
    public function show(Category $category){ return $category; }
    public function update(UpdateCategoryRequest $request, Category $category){ return $category->update($request->all()); }
    public function destroy($id){
        $category = Category::findOrFail($id);
        $category->delete();
        return 204;
    }
}
