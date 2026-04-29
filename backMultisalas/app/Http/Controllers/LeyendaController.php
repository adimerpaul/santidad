<?php

namespace App\Http\Controllers;

use App\Models\Leyenda;
use App\Http\Requests\StoreLeyendaRequest;
use App\Http\Requests\UpdateLeyendaRequest;

class LeyendaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     * @param  \App\Http\Requests\StoreLeyendaRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreLeyendaRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Leyenda  $leyenda
     * @return \Illuminate\Http\Response
     */
    public function show(Leyenda $leyenda)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Leyenda  $leyenda
     * @return \Illuminate\Http\Response
     */
    public function edit(Leyenda $leyenda)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateLeyendaRequest  $request
     * @param  \App\Models\Leyenda  $leyenda
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateLeyendaRequest $request, Leyenda $leyenda)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Leyenda  $leyenda
     * @return \Illuminate\Http\Response
     */
    public function destroy(Leyenda $leyenda)
    {
        //
    }
}
