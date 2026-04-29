<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Detail extends Model
{
    use HasFactory;
    protected $fillable = [
        'actividadEconomica',
        'codigoProductoSin',
        'cantidad',
        'precioUnitario',
        'subTotal',
        'sale_id',
        'programa_id',
        'pelicula_id',
        'descripcion',
    ];
}
