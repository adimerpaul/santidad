<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $fillable = ['agencia_id', 'mensaje', 'detalle', 'leida'];
    protected $table = 'notificaciones'; // ✅ Corrige el nombre de tabla
}