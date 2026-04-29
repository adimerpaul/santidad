<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Momentaneo extends Model
{
    use HasFactory;
    protected $fillable=[
        "fila",
        "columna",
        "letra",
        "user_id",
        "fecha",
        "precio",
        "pelicula",
        "promo",
        "pelicula_id",
        "user_id",
        "programa_id",
    ];
}
