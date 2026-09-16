<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendedor extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'celular',
        'client_id'
    ];

    // Esta función es la clave para que funcione el with('client')
    public function client()
    {
        // Relación: Un vendedor pertenece a un Cliente (Proveedor)
        return $this->belongsTo(Client::class, 'client_id');
    }
}