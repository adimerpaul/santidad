<?php

namespace App\Http\Controllers;

use App\Models\Sala;
use App\Models\Seat;
use App\Http\Requests\StoreSalaRequest;
use App\Http\Requests\UpdateSalaRequest;
use App\Http\Requests\StoreSeatRequest;
use App\Http\Requests\UpdateSeatRequest;
use Illuminate\Support\Facades\DB;

class SalaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return Sala::with('seats')->get();
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
     * @param  \App\Http\Requests\StoreSalaRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSalaRequest $request)
    {
        //
        //return $request;
        $sala=new Sala;
        $sala->nro=$request->nro;
        $sala->nombre=$request->nombre;
        $sala->capacidad=$request->capacidad;
        $sala->filas=$request->filas;
        $sala->columnas=$request->columnas;
        $sala->save();

        foreach ($request->seats as $r){

            $seat=new Seat;

            $seat->fila=$r['fila'];
            $seat->columna=$r['columna'];
            $seat->letra=$r['letra'];
            $seat->activo=$r['activo'];
            $seat->sala_id=$sala->id;
            $seat->save();
            //return $seat;
        }
        return Sala::where('id',$sala->id)->with('seats')->first();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Sala  $sala
     * @return \Illuminate\Http\Response
     */
    public function show(Sala $sala)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Sala  $sala
     * @return \Illuminate\Http\Response
     */
    public function edit(Sala $sala)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateSalaRequest  $request
     * @param  \App\Models\Sala  $sala
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSalaRequest $request, Sala $sala)
    {
        //
        $sala=Sala::find($request->id);
        $sala->nro=$request->nro;
        $sala->nombre=$request->nombre;
        $sala->capacidad=$request->capacidad;
        $sala->save();

        foreach ($request->seats as $r){

            $seat=Seat::find($r['id']);
            $seat->activo=$r['activo'];
            $seat->save();
            //return $seat;
        }
        return Sala::where('id',$sala->id)->with('seats')->first();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Sala  $sala
     * @return \Illuminate\Http\Response
     */
    public function destroy(Sala $sala)
    {
        //
        DB::SELECT("DELETE from seats where sala_id=$sala->id");
        $sala->delete();

    }
}
