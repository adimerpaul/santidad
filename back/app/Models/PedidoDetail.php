<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'pedido_id',
        'product_id',
        'cantidad'
    ];

    // Relación con el pedido
    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    // Relación con el producto
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}