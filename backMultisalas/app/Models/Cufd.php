<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cufd extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable= [
        'codigo',
        'codigoControl',
        'direccion',
        'fechaVigencia',
        'codigoPuntoVenta',
        'codigoSucursal',
    ];
    public function sales(){
        return $this->hasMany(Sale::class);
    }
}
