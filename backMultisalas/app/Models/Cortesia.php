<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cortesia extends Model
{
    use HasFactory;
    protected $fillable = [
        'date',
        'time',
        'user_id',
        'sale_id',
    ];
    public function user(){
        return $this->belongsTo('App\Models\User');
    }
    public function sale(){
        return $this->belongsTo('App\Models\Sale');
    }
}
