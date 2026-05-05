<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiatEnvio extends Model
{
    protected $table = 'siat_envios';

    protected $fillable = [
        'sale_id',
        'codigo_evento',
        'codigo_recepcion',
        'estado',
        'ultimo_mensaje',
    ];

    public function sale()
    {
        return $this->belongsTo(Sales::class);
    }
}
