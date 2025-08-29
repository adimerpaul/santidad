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
        'cantidadSucursal1',
        'cantidadSucursal2',
        'cantidadSucursal3',
        'cantidadSucursal4',
        'cantidadAlmacen',
        'costo',
        'precio',
        'precioAntes',
        'unidad',
        'activo',
        'imagen',
        'descripcion',
        "category_id",
        "agencia_id",
        'registroSanitario',
        'paisOrigen',
        'nombreComun',
        'composicion',
        'marca',
        'distribuidora',
        'subcategory_id',
        'porcentaje',
        'en_oferta',
    ];
       // 👇 Aquí lo pegas
    protected $casts = [
        'en_oferta'   => 'boolean',
         'activo'    => 'boolean',
    ];
    public function category(){
        return $this->belongsTo(Category::class);
    }
    public function agencia(){
        return $this->belongsTo(Agencia::class);
    }
    public function buys(){
        return $this->hasMany(Buy::class);
    }
}
