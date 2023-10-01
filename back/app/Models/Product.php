<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable=[
        'nombre',
        'barra',
        'cantidad',
        'costo',
        'precio',
        'precioAntes',
        'unidad',
        'activo',
        'imagen',
        'descripcion',
        "category_id",
        "agencia_id"
    ];
    public function category(){
        return $this->belongsTo(Category::class);
    }
    public function agencia(){
        return $this->belongsTo(Agencia::class);
    }
}
