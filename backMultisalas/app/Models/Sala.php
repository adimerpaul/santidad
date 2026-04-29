<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sala extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable=['nro','nombre','filas','columnas','capacidad'];

    public function seats()
    {
        return $this->hasMany(Seat::class);
    }
}
