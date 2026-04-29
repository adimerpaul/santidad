<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Programa extends Model
{
    use HasFactory;
    protected $fillable= [
        "fecha",
        "horaInicio",
        "horaFin",
        "subtitulada",
        "formato",
        "activo",
        "nroFuncion",
        "user_id",
        "movie_id",
        "sala_id",
        "price_id",
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }
    public function sala()
    {
        return $this->belongsTo(Sala::class);
    }
    public function price()
    {
        return $this->belongsTo(Price::class);
    }

    public function tickets(){
        return $this->hasMany(Ticket::class);
    }

    public function webMovies()
    {
        return $this->belongsToMany(WebMovie::class, 'web_movie_programa');
    }
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
