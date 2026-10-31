<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InternalCombo extends Model
{
    use SoftDeletes;
    protected $guarded = ['id'];
    protected $casts = ['activo' => 'boolean', 'permanente' => 'boolean', 'porcentaje' => 'float', 'version' => 'integer'];
    public function items() { return $this->hasMany(InternalComboItem::class); }
}
