<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebMovie extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'tipo',
        'carrusel_tipo',
        'tmdb_id',
        'imdb_id',
        'titulo_original',
        'web_studio_id',
        'imagen',
        'backdrop_imagen',
        'trailer_youtube',
        'url_video_youtube',
        'bucket',
        'puntaje_web',
        'puntaje_tomatoes',
        'puntaje_ibm',
        'puntaje_metacritic',
        'votos_total',
        'popularidad',
        'tagline',
        'url_oficial',
        'calidad',
        'descuento',
        'anio',
        'fecha_estreno',
        'tele',
        'descripcion',
        'me_gusta',
        'duracion',
        'genero',
        'pais',
        'idioma',
        'clasificacion',
        'premios',
        'estado',
        'api_payload',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'api_payload' => 'array',
    ];

    public function studio()
    {
        return $this->belongsTo(WebStudio::class, 'web_studio_id');
    }

    public function actores()
    {
        return $this->hasMany(WebMovieActor::class);
    }

    public function programas()
    {
        return $this->belongsToMany(Programa::class, 'web_movie_programa')
            ->with('movie', 'sala', 'price');
    }
}
