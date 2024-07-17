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
    public function store(Request $request){ return Carousel::create($request->all()); }
    public function update(Request $request, $id){
        $carousel = Carousel::findOrFail($id);
        $carousel->update($request->all());
        return $carousel;
    }
}
