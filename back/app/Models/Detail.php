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
        'descripcion',
        'sale_id',
        'product_id',
        'user_id'
    ];
    public function sale(){ return $this->belongsTo(Sales::class); }
    public function product(){ return $this->belongsTo(Product::class); }
    public function user(){ return $this->belongsTo(User::class); }

}
