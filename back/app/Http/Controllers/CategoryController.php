<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index()
    {
        return Cache::rememberForever('categories_list', function () {
            return Category::all();
        });
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create($request->all());
        $this->clearCache();
        return $category;
    }

    public function show(Category $category)
    {
        return $category;
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $res = $category->update($request->all());
        $this->clearCache();
        return $res;
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $res = $category->delete();
        $this->clearCache();
        return 204;
    }

    private function clearCache()
    {
        Cache::forget('categories_list');
    }
}
