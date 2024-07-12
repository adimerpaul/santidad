<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agencia extends Model
{
    use HasFactory;
    protected $fillable = ['nombre','direccion','telefono','atencion','horario','facebook','whatsapp','gps','latitud','longitud','status'];
    protected $hidden = ['created_at','updated_at'];
}
