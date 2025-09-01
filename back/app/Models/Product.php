<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'barra',
        'cantidad',
        'cantidadSucursal1',
        'cantidadSucursal2',
        'cantidadSucursal3',
        'cantidadSucursal4',
        'cantidadAlmacen',
        'costo',
        'precio',
        'precioAntes',
        'unidad',
        'activo',
        'imagen',
        'descripcion',
        'category_id',
        'agencia_id',
        'registroSanitario',
        'paisOrigen',
        'nombreComun',
        'composicion',
        'marca',
        'distribuidora',
        'subcategory_id',
        'porcentaje',
        'en_oferta',
    ];

    protected $casts = [
        'en_oferta' => 'boolean',
        // ⚠️ Quitamos 'activo' => 'boolean' porque la BD usa ACTIVO/INACTIVO
    ];

    // ===== Normalización de 'activo' =====
    public function setActivoAttribute($value)
    {
        $v = is_string($value) ? trim($value) : $value;
        $on = false;

        if (is_bool($v)) {
            $on = $v;
        } elseif (is_numeric($v)) {
            $on = ((int) $v) === 1;
        } elseif (is_string($v)) {
            $up = strtoupper($v);
            $on = in_array($up, ['ACTIVO', 'TRUE', 'ON', 'YES', '1'], true);
        }

        $this->attributes['activo'] = $on ? 'ACTIVO' : 'INACTIVO';
    }

    public function getActivoAttribute($value)
    {
        // Siempre devolver 'ACTIVO' o 'INACTIVO' de forma consistente
        $up = strtoupper((string) $value);
        return in_array($up, ['ACTIVO', '1', 'TRUE', 'ON', 'YES'], true) ? 'ACTIVO' : 'INACTIVO';
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function agencia()
    {
        return $this->belongsTo(Agencia::class);
    }

    public function buys()
    {
        return $this->hasMany(Buy::class);
    }
}
