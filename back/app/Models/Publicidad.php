<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publicidad extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'file_id',
        'type',
        'url',
        'active',
        'agencia_id'
    ];

    public function agencia()
    {
        return $this->belongsTo(Agencia::class);
    }
}
