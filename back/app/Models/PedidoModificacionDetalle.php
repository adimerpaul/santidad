<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoModificacionDetalle extends Model
{
    use HasFactory;
    // AGREGA ESTO:
    protected $table = 'pedido_modificacion_detalles';

    protected $fillable = [
        'modificacion_id',
        'pedido_detail_id',
        'cantidad_anterior',
        'cantidad_nueva',
        'accion'
    ];

    // Relación con la modificación principal
    public function modificacion()
    {
        return $this->belongsTo(PedidoModificacion::class, 'modificacion_id');
    }

    // Relación con el detalle del pedido
    public function pedidoDetail()
    {
        return $this->belongsTo(PedidoDetail::class, 'pedido_detail_id');
    }

    // Relación con el producto (a través de pedidoDetail)
    public function product()
    {
        return $this->hasOneThrough(
            Product::class,
            PedidoDetail::class,
            'id', // Foreign key on PedidoDetail table
            'id', // Foreign key on Product table
            'pedido_detail_id', // Local key on this table
            'product_id' // Local key on PedidoDetail table
        );
    }
}