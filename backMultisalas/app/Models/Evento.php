<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    use HasFactory;
    protected $fillable = ['nombre','descripcion','fecha','hora','lugar','imagen','estado'];
    protected $hidden = ['created_at', 'updated_at'];
}
