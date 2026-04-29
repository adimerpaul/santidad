<?php

namespace App\Http\Controllers;

use App\Models\Programa;
use App\Http\Requests\StoreProgramaRequest;
use App\Http\Requests\UpdateProgramaRequest;

class ProgramaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return Programa::whereDate('fecha','>=',now())->with('movie')->with('sala')->with('price')->get();
    }

    public function listProgramacion(){
        return Programa::whereDate('fecha','>=',now())->with('movie')->with('sala')->with('price')->get();

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
     * @param  \App\Http\Requests\StoreProgramaRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProgramaRequest $request)
    {
        $duracion=$request->movie['duracion'];
        $hora=$request->hora;
        $horafin= strtotime ( '+'.$duracion.' minutes' , strtotime ($hora) );
        //return   $request->fechaini.' '.date ( 'H:i' , $horafin);
        $horafin=date('H:i',$horafin).':00';

        $fecha1=  date_create($request->fechaini);
        $fecha2=  date_create($request->fechafin);
        $contador = date_diff($fecha1, $fecha2);
        $dias=$contador->format('%a') + 1;
        $fecha=$request->fechaini;
        for ($i=1;$i<=$dias;$i++){
            $programa=new Programa;
            $programa->fecha=$fecha;
            $numfuncion=Programa::whereDate('fecha',$fecha)->count() + 1;

            $programa->horaInicio=date('Y-m-d H:i:s', strtotime("$fecha $hora"));
            $programa->horaFin=date('Y-m-d H:i:s', strtotime("$fecha $horafin"));
            $programa->subtitulada=$request->subtitulada;
            $programa->activo='ACTIVO';
            $programa->nroFuncion=$numfuncion;
            //$programa->user_id=$request->user()->id;
            $programa->movie_id=$request->movie['id'];
            $programa->sala_id=$request->sala['id'];
            $programa->price_id=$request->price['id'];
            $programa->save();
            //return $programa;
            $fecha =  date ( 'Y-m-d' , strtotime ( '+1 day' , strtotime ($fecha) ));

        }

// El resultados sera 3 dias
        return ;


    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Programa  $programa
     * @return \Illuminate\Http\Response
     */
    public function show(Programa $programa)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Programa  $programa
     * @return \Illuminate\Http\Response
     */
    public function edit(Programa $programa)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateProgramaRequest  $request
     * @param  \App\Models\Programa  $programa
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProgramaRequest $request, Programa $programa)
    {
        //
        $programa=Programa::find($request->id);
        $duracion=$request->movie['duracion'];
        $hora=$request->hora;
        $horafin= strtotime ( '+'.$duracion.' minutes' , strtotime ($hora) );
        //return   $request->fechaini.' '.date ( 'H:i' , $horafin);
        $horafin=date('H:i',$horafin).':00';
        $fecha=$programa->fecha;


            $programa->horaInicio=date('Y-m-d H:i:s', strtotime("$fecha $hora"));
            $programa->horaFin=date('Y-m-d H:i:s', strtotime("$fecha $horafin"));
            $programa->subtitulada=$request->subtitulada;
            $programa->activo=$request->activo;
            $programa->movie_id=$request->movie['id'];
            $programa->sala_id=$request->sala['id'];
            $programa->price_id=$request->price['id'];
            $programa->save();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Programa  $programa
     * @return \Illuminate\Http\Response
     */
    public function destroy(Programa $programa)
    {
        //
        $programa->delete();
    }
}
