<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Distributor extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable= [
        'nombre',
        'dir',
        'loc',
        'nit',
        'tel',
        'email',
        'responsable',
    ];
}
