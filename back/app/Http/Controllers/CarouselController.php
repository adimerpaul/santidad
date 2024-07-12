<?php

namespace App\Http\Controllers;

use App\Models\Carousel;
use App\Http\Requests\StoreCarouselRequest;
use App\Http\Requests\UpdateCarouselRequest;

class CarouselController extends Controller{
    public function index(){ return Carousel::where('status','active')->get(); }
    public function show(Carousel $carousel){ return $carousel; }
    public function store(StoreCarouselRequest $request){ return Carousel::create($request->all()); }
    public function update(UpdateCarouselRequest $request, Carousel $carousel){ return $carousel->update($request->all()); }
}
