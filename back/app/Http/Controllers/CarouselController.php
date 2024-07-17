<?php

namespace App\Http\Controllers;

use App\Models\Carousel;
use App\Http\Requests\StoreCarouselRequest;
use App\Http\Requests\UpdateCarouselRequest;
use Illuminate\Http\Request;

class CarouselController extends Controller{
    function carouselsPage(){ return Carousel::where('status','active')->get(); }
    public function index(){ return Carousel::orderBy('id','desc')->get(); }
    public function show(Carousel $carousel){ return $carousel; }
    function storeFile(Request $request, $id){
        $validated = $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        $imageName = time().'.'.$request->file->extension();
        $request->file->move(public_path('images'), $imageName);
        $request->merge(['image' => $imageName]);
        $request->merge(['url' => $imageName]);
        $request->merge(['imageResponsive' => $imageName]);
        $carousel = Carousel::findOrFail($id);
        $carousel->update($request->all());
        return $carousel;
    }
    public function store(Request $request){
        $validated = $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        $imageName = time().'.'.$request->file->extension();
        $request->file->move(public_path('images'), $imageName);
        $request->merge(['image' => $imageName]);
        $request->merge(['url' => $imageName]);
        $request->merge(['imageResponsive' => $imageName]);


        return Carousel::create($request->all());
    }
    public function update(Request $request, $id){
        $url = isset($request->url) ? $request->url : '';
        $request->merge(['url' => $url]);
        $carousel = Carousel::findOrFail($id);
        $carousel->update($request->all());
        return $carousel;
    }
}
