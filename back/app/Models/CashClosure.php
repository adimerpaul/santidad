<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashClosure extends Model
{
    use HasFactory;

    protected $fillable = [
        'agencia_id',
        'user_id',
        'closed_by_user_id',
        'fecha_apertura',
        'fecha_cierre',
        'monto_fisico',
        'monto_sistema_efectivo',
        'monto_sistema_digital',
        'monto_sistema_total',
        'diferencia',
        'observaciones',
        'observaciones_apertura',
        'estado',
    ];

    public function agencia()
    {
        return $this->belongsTo(Agencia::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function closedByUser()
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }
}
