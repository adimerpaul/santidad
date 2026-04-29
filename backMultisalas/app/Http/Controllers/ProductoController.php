<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRubroRequest;
use App\Models\Producto;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Rubro;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return Producto::with('rubro')->get();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreProductoRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProductoRequest $request)
    {
        //
        $producto=new Producto;
        $producto->nombre=strtoupper($request->nombre);
        $producto->descripcion=$request->descripcion;
        $producto->precio=$request->precio;
        $producto->activo='ACTIVO';
        $producto->imagen=$request->imagen;
        $producto->color=$request->color;
        $producto->rubro_id=$request->rubro_id;
        $producto->save();


        return Producto::where('id',$producto->id)->with('rubro')->first();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Producto  $producto
     * @return \Illuminate\Http\Response
     */
    public function show(Producto $producto)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Producto  $producto
     * @return \Illuminate\Http\Response
     */
    public function edit(Producto $producto)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateProductoRequest  $request
     * @param  \App\Models\Producto  $producto
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProductoRequest $request, Producto $producto)
    {
        //
        $producto=Producto::find($request->id);
        $producto->nombre=strtoupper($request->nombre);
        $producto->descripcion=$request->descripcion;
        $producto->precio=$request->precio;
        $producto->activo=$request->activo;
        $producto->color=$request->color;
        $producto->rubro_id=$request->rubro_id;
        $producto->save();

        return Producto::where('id',$producto->id)->with('rubro')->first();

    }
    public function upimagenproducto(UpdateProductoRequest $request)
    {
        //
        $producto= Producto::find($request->id);
        $producto->imagen=$request->imagen;
        $producto->save();

        $producto=Producto::with('rubro')->where('id',$request->id)->first();
        return $producto;
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Producto  $producto
     * @return \Illuminate\Http\Response
     */
    public function destroy(Producto $producto)
    {
        //
        $producto->delete();
    }
}
