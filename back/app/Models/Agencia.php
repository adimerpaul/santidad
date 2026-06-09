<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agencia extends Model
{
    use HasFactory;
    protected $fillable = ['nombre','sucursal','direccion','telefono','atencion','horario','facebook','whatsapp','gps','latitud','longitud','status'];
    protected $hidden = ['created_at','updated_at'];
}
