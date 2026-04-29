<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

class ListaVentaCandyController extends Controller
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if($request->todo){
            return Sale::whereDate('fechaEmision','>=', $request->fechaIni)
            ->whereDate('fechaEmision','<=', $request->fechaFin)
            ->where('tipo', '=', 'CANDY')
            ->with('user')
            ->with('client')
            ->with('details')
            ->orderBy('id', 'desc')
            ->get();
        }
        else{
        return Sale::whereDate('fechaEmision','>=', $request->fechaIni)
            ->whereDate('fechaEmision','<=', $request->fechaFin)
            ->where('tipo', '=', 'CANDY')
            ->where('user_id', '=', $request->user()->id)
            ->with('user')
            ->with('client')
            ->with('details')
            ->orderBy('id', 'desc')
            ->get();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
