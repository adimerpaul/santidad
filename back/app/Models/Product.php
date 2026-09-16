<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $appends = ['precioVenta'];

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

    public function setPrecioAttribute($value)
    {
        $this->attributes['precio'] = ($value !== null && $value !== '') ? Money::roundToCents($value) : null;
    }

    public function setPrecioAntesAttribute($value)
    {
        $this->attributes['precioAntes'] = ($value !== null && $value !== '') ? Money::roundToCents($value) : null;
    }

    public function setCostoAttribute($value)
    {
        $this->attributes['costo'] = ($value !== null && $value !== '') ? round((float) $value, 1) : null;
    }

    /** Precio unitario efectivo que se cobra, redondeado a Bs 0,10. */
    public function precioVentaRedondeado(): float
    {
        $precio = Money::roundToCents($this->precio);
        $porcentaje = max(0, min(100, (float) ($this->porcentaje ?? 0)));

        return Money::roundToTenth($precio - ($precio * $porcentaje / 100));
    }

    public function getPrecioVentaAttribute($value = null): float
    {
        if (array_key_exists('precioVenta', $this->attributes)) {
            return (float) $this->attributes['precioVenta'];
        }

        return $this->precioVentaRedondeado();
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
