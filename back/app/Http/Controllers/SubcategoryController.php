<?php

namespace App\Http\Controllers;

use App\Models\Subcategory;
use Illuminate\Http\Request;

class SubcategoryController extends Controller{
    public function index(){
        return Subcategory::with('category')->get();
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
