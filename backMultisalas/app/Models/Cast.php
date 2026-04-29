<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cast extends Model
{
    use HasFactory;
    protected $fillable = ['nombre','imagen','proximo_id'];
    protected $hidden = ['created_at', 'updated_at'];
    public function proximo(){
        return $this->belongsTo(Proximo::class);
    }
}
