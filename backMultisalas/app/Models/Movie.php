<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Movie extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable=[
        'nombre',
        'duracion',
        'paisOrigen',
        'genero',
        'sipnosis',
        'urlTrailer',
        'formato',
        'imagen',
        'clasificacion',
        'fechaEstreno',
        'distributor_id',
        'ratingPublic',
        'ratingCritica',
    ];
    public function distributor()
    {
        return $this->belongsTo(Distributor::class);
    }
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    public function programas()
    {
        return $this->hasMany(Programa::class);
    }
}
