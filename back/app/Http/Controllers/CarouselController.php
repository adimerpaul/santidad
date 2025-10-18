<?php

namespace App\Http\Controllers;

use App\Models\Carousel;
use Illuminate\Http\Request;

class CarouselController extends Controller
{
    /**
     * Carrusel principal (tipo: Normal)
     */
    public function carouselsPage()
    {
        return Carousel::where('status', 'active')
            ->where('tipo', 'Normal')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Carrusel mini (tipo: Mini) — devuelve hasta 10
     */
    public function carouselsMini()
    {
        return Carousel::where('status', 'active')
            ->where('tipo', 'Mini')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Carrusel medio (tipo: Medio) — para reemplazar el banner
     */
    public function carouselsMedio()
    {
        return Carousel::where('status', 'active')
            ->where('tipo', 'Medio')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Listado total (admin)
     */
    public function index()
    {
        return Carousel::orderBy('id', 'desc')->get();
    }

    /**
     * Mostrar uno (admin)
     */
    public function show(Carousel $carousel)
    {
        return $carousel;
    }

    /**
     * Crear (imagen obligatoria)
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:2048',
        ]);

        $imageName = time() . '.' . $request->file->extension();
        $request->file->move(public_path('images'), $imageName);

        // Normaliza campos de imagen
        $request->merge([
            'image'           => $imageName,
            'url'             => $imageName,
            'imageResponsive' => $imageName,
        ]);

        return Carousel::create($request->all());
    }

    /**
     * Actualizar SIN cambiar imagen (usa storeFile para cambiarla)
     */
    public function update(Request $request, $id)
    {
        $request->merge(['url' => $request->url ?? '']);

        $carousel = Carousel::findOrFail($id);
        $carousel->update($request->all());

        return $carousel;
    }

    /**
     * Actualizar CON archivo (imagen opcional)
     */
    public function storeFile(Request $request, $id)
    {
        $carousel = Carousel::findOrFail($id);

        if ($request->hasFile('file')) {
            $request->validate([
                'file' => 'nullable|image|max:2048',
            ]);

            $imageName = time() . '.' . $request->file->extension();
            $request->file->move(public_path('images'), $imageName);

            $request->merge([
                'image'           => $imageName,
                'url'             => $imageName,
                'imageResponsive' => $imageName,
            ]);
        }

        $carousel->update($request->all());

        return $carousel;
    }

    /**
     * Eliminar (admin)
     */
    public function destroy($id)
    {
        $carousel = Carousel::findOrFail($id);
        $carousel->delete();

        return response()->json(['deleted' => true]);
    }
}
