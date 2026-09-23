<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carousel extends Model
{
    use HasFactory;
//$table->string('url');
//$table->string('image');
//$table->string('imageResponsive');
//$table->string('status')->default('active');
    protected $fillable = ['url','image','imageResponsive','status','tipo'];
    protected $hidden = ['created_at','updated_at'];
}
