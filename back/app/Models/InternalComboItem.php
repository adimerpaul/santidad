<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternalComboItem extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['cantidad' => 'integer', 'precio_base' => 'float', 'precio_unitario' => 'float'];
    public function product() { return $this->belongsTo(Product::class); }
}
