<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'withdrawal_report_id',
        'buy_id',
        'product_id',
        'agencia_id',
        'cantidad',
        'stock_sistema',
        'conteo_fisico',
        'tipo',
        'descripcion',
        'estado',
        'prorroga_hasta',
        'admin_descripcion',
        'cantidad_original',
        'agencia_id_original',
    ];

    public function report()
    {
        return $this->belongsTo(WithdrawalReport::class, 'withdrawal_report_id');
    }

    public function buy()
    {
        return $this->belongsTo(Buy::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function agencia()
    {
        return $this->belongsTo(Agencia::class);
    }
}
