<?php

namespace App\Http\Controllers;

use App\Models\Momentaneo;
use App\Http\Requests\StoreMomentaneoRequest;
use App\Http\Requests\UpdateMomentaneoRequest;
use Illuminate\Http\Request;

class MomentaneoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Momentaneo::where('user_id',$request->user()->id)->get();
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
     * @param  \App\Http\Requests\StoreMomentaneoRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMomentaneoRequest $request)
    {
        if (Momentaneo::where("user_id",$request->user()->id)
            ->where("programa_id",$request->programa_id)
            ->where("fila",$request->fila)
            ->where("columna",$request->columna)
            ->where("letra",$request->letra)->count()==1){
            return 1;
        }
        Momentaneo::create($request->all());
    }
    public function momentaneoDelete(Request $request)
    {
        Momentaneo::where("user_id",$request->user()->id)
            ->where("programa_id",$request->programa_id)
            ->where("fila",$request->fila)
            ->where("columna",$request->columna)
            ->where("letra",$request->letra)
            ->delete();
    }
    public function momentaneoDeleteUser(Request $request)
    {
        Momentaneo::where("user_id",$request->user()->id)
            ->where("programa_id",$request->programa_id)
            ->delete();
    }
    public function momentaneoDeleteAll(Request $request)
    {
        Momentaneo::where("user_id",$request->user()->id)
            ->delete();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Momentaneo  $momentaneo
     * @return \Illuminate\Http\Response
     */
    public function show(Momentaneo $momentaneo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Momentaneo  $momentaneo
     * @return \Illuminate\Http\Response
     */
    public function edit(Momentaneo $momentaneo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateMomentaneoRequest  $request
     * @param  \App\Models\Momentaneo  $momentaneo
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateMomentaneoRequest $request, Momentaneo $momentaneo)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Momentaneo  $momentaneo
     * @return \Illuminate\Http\Response
     */
    public function destroy(Momentaneo $momentaneo)
    {

    }
}
