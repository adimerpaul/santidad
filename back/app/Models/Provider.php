<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;

    // --- ESTA ES LA SOLUCIÓN MÁGICA ---
    // Le decimos que use la tabla 'clients' en lugar de buscar 'providers'
    protected $table = 'clients'; 
    // ----------------------------------

    protected $guarded = [];
    
    // Opcional: Un "Scope" para que siempre que llames a Provider,
    // solo traiga los que son proveedores y no los clientes normales.
    protected static function booted()
    {
        static::addGlobalScope('soloProveedores', function ($builder) {
            $builder->where('clienteProveedor', 'Proveedor');
        });
    }
}