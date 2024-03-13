<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Buy extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'product_id',
        'agencia_id',
        'lote',
        'quantity',
        'total',
        'price',
        'dateExpiry',
        'date',
        'time',
        'proveedor_id',
    ];
    protected $appends = ['diasPorVencer'];
    public function product(){
        return $this->belongsTo(Product::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function proveedor(){
        return $this->belongsTo(Client::class);
    }

    public function getDiasPorVencerAttribute()
    {
        $formatted_dt1 = Carbon::parse($this->dateExpiry);
        $formatted_dt2 = Carbon::now();
        return $formatted_dt1->diffInDays($formatted_dt2);
    }
    // En el modelo Buy.php
    public function scopeUserFilter($query, $userId)
    {
        return $query->when($userId != 1, function ($q) use ($userId) {
            return $q->where('agencia_id', auth()->user()->agencia_id);
        });
    }
}
