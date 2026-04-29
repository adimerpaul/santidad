<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRubroRequest;
use App\Models\Movie;
use App\Http\Requests\StoreMovieRequest;
use App\Http\Requests\UpdateMovieRequest;
use App\Models\Rubro;

class MovieController extends Controller{
    public function index(){
        $movies= Movie::with('distributor')
            ->orderBy('id','desc')
            ->get();
        foreach ($movies as $movie){
            if (!file_exists(public_path('imagen/'.$movie->imagen))){
                $movie->imagen='default.jpg';
            }
        }
        return $movies;
    }
    public function store(StoreMovieRequest $request){
        $movie= Movie::create($request->all());
        return Movie::where('id',$movie->id)->with('distributor')->first();
    }
    public function update(UpdateMovieRequest $request, Movie $movie)
    {
        $movie->update($request->all());
        return $movie->with('distributor')->get();
    }
    public function upimagenmovie(UpdateMovieRequest $request){
        $movie= Movie::find($request->id);
        $movie->imagen=$request->imagen;
        $movie->save();

        $movie=Movie::with('distributor')->where('id',$request->id)->first();
        return $movie;
    }
    public function upimagenrubro(UpdateRubroRequest $request){
        $rubro= Rubro::find($request->id);
        $rubro->imagen=$request->imagen;
        $rubro->save();

        return $rubro;
    }
    public function destroy(Movie $movie)
    {
        $movie->delete();
    }
}
