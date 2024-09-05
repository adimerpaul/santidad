<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BuyDetail extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = [
        'buy_id',
        'product_id',
        'user_id',
        'quantity',
        'fecha',
        'hora'
    ];
    public function buy(){
        return $this->belongsTo(Buy::class);
    }
    public function product(){
        return $this->belongsTo(Product::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }
    protected $hidden = ['created_at','updated_at','deleted_at'];
}
