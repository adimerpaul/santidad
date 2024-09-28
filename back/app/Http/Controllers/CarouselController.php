<?php

namespace App\Http\Controllers;

use App\Models\Carousel;
use App\Http\Requests\StoreCarouselRequest;
use App\Http\Requests\UpdateCarouselRequest;
use Illuminate\Http\Request;

class CarouselController extends Controller{
    function carouselsPage(){
        return Carousel::where('status','active')->where('tipo','Normal')->get();
    }
    function carouselsMini(){
        return Carousel::where('status','active')->where('tipo','Mini')->get();
    }
    public function index(){ return Carousel::orderBy('id','desc')->get(); }
    public function show(Carousel $carousel){ return $carousel; }
    function storeFile(Request $request, $id) {
        // Validar si se envía una imagen, pero no es obligatorio


        // Buscar el registro en la base de datos
        $carousel = Carousel::findOrFail($id);

        // Si el archivo existe, procesarlo
        if ($request->hasFile('file')) {
            $validated = $request->validate([
                'file' => 'nullable|image|max:2048',
            ]);
            $imageName = time() . '.' . $request->file->extension();
            $request->file->move(public_path('images'), $imageName);

            // Actualizar los campos de la imagen si hay una
            $request->merge([
                'image' => $imageName,
                'url' => $imageName,
                'imageResponsive' => $imageName,
            ]);
        }

        // Actualizar el resto de los campos
        $carousel->update($request->all());

        return $carousel;
    }
    public function store(Request $request){
        $validated = $request->validate([
            'file' => 'required|image|max:2048',
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
