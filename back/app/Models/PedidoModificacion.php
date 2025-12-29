<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoModificacion extends Model
{
    use HasFactory;
    // AGREGA ESTO:
    protected $table = 'pedido_modificaciones';

    protected $fillable = [
        'pedido_id',
        'user_id',
        'accion',
        'estado_anterior',
        'estado_nuevo',
        'observacion'
    ];

    // Relación con el pedido
    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    // Relación con el usuario que hizo la modificación
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación con los detalles de modificación
    public function detalles()
    {
        return $this->hasMany(PedidoModificacionDetalle::class, 'modificacion_id');
    }
}