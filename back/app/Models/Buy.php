<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];
    public function product(){
        return $this->belongsTo(Product::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }
    // En el modelo Buy.php
    public function scopeUserFilter($query, $userId)
    {
        return $query->when($userId != 1, function ($q) use ($userId) {
            return $q->where('agencia_id', auth()->user()->agencia_id);
        });
    }
}
