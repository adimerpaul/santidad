<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $fillable = ['agencia_id', 'agencia_origen_id','mensaje', 'detalle', 'leida'];
    protected $table = 'notificaciones'; // ✅ Corrige el nombre de tabla

    public function agencia()
    {
        // Esta relación nos dirá a QUIÉN se le envió (el destino)
        return $this->belongsTo(Agencia::class, 'agencia_id');
    }
}