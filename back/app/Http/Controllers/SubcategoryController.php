<?php

namespace App\Http\Controllers;

use App\Models\Subcategory;
use Illuminate\Http\Request;

class SubcategoryController extends Controller{
    public function index(){
//        ordenar por categoria
        $subcategories= Subcategory::with('category')
            ->orderBy('category_id')
            ->get();
        $subcategories->each(function ($subcategory){
            $subcategory->nameComplete = $subcategory->category->name.' '.$subcategory->name;
        });
        return $subcategories;
    }

    public function store(Request $request)
    {
        return Subcategory::create($request->all());
    }

    public function show(Subcategory $subcategory)
    {

    }

    public function update(Request $request, Subcategory $subcategory)
    {
        return $subcategory->update($request->all());
    }

    public function destroy(Subcategory $subcategory)
    {
        return $subcategory->delete();
    }
}
