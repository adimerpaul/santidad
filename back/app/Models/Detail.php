<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Detail extends Model
{
    use HasFactory;
    protected $fillable = [
        'combo_id', 'combo_nombre', 'combo_grupo', 'combo_cantidad', 'combo_version',
        'actividadEconomica',
        'codigoProductoSin',
        'cantidad',
        'precioUnitario',
        'precioOriginal',
        'promocion_id',
        'promocion_nombre',
        'promocion_porcentaje',
        'subTotal',
        'descripcion',
        'sale_id',
        'product_id',
        'user_id'
    ];
    public function sale(){ return $this->belongsTo(Sales::class); }
    public function product(){ return $this->belongsTo(Product::class); }
    public function user(){ return $this->belongsTo(User::class); }

}
