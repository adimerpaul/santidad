<?php

namespace App\Http\Controllers;

use App\Models\Cortesia;
use App\Http\Requests\StoreCortesiaRequest;
use App\Http\Requests\UpdateCortesiaRequest;
use Illuminate\Http\Request;

class CortesiaController extends Controller{
    public function index(){return Cortesia::orderBy('id', 'desc')->with('user')->with('sale')->get();}
    public function free(){
        return Cortesia::whereNull('user_id')->limit(30)->get();
    }
    public function show($id){return Cortesia::find($id);}
    public function store(Request $request){
        for ($i=0; $i < $request->cantidad; $i++) {
            Cortesia::create();
        }
    }
    public function update(Request $request, $id){$cortesia = Cortesia::findOrFail($id);$cortesia->update($request->all());return $cortesia;}
    public function destroy($id){$cortesia = Cortesia::findOrFail($id);$cortesia->delete();return $cortesia;}
}
