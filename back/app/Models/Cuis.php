<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cuis extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'codigo',
        'fechaVigencia',
        'fechaCreacion',
        'codigoPuntoVenta',
        'codigoSucursal',
    ];

    protected $casts = [
        'fechaVigencia' => 'datetime',
        'fechaCreacion' => 'datetime',
    ];
}
