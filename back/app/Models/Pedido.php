<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// --- IMPORTANTE: AGREGA ESTA LÍNEA ---
use App\Models\Provider; 
// -------------------------------------

class Pedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'agencia_id', 
        'user_id', 
        'proveedor_id', 
        'fecha_pedido', 
        'estado', 
        'observacion'
    ];

    // Relaciones existentes...
    public function agencia()
    {
        return $this->belongsTo(Agencia::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function detalles()
    {
        return $this->hasMany(PedidoDetail::class);
    }

    // Esta es la relación que estaba fallando
    public function proveedor()
    {
        // Esto buscará el id en la tabla 'clients' gracias al cambio del Paso 1
        return $this->belongsTo(Provider::class, 'proveedor_id');
    }
}