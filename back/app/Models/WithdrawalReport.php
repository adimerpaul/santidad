<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'agencia_id',
        'mes',
        'anio',
        'user_id',
        'estado',
        'tipo',
        'observaciones',
    ];

    public function agencia()
    {
        return $this->belongsTo(Agencia::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(WithdrawalItem::class);
    }
}
