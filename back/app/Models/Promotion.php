<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Promotion extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'porcentaje',
        'alcance',
        'category_id',
        'fecha_inicio',
        'fecha_fin',
        'permanente',
        'activo',
        'todas_agencias',
        'canal_fisico',
        'canal_web',
        'canal_app',
        'mostrar_en_ofertas',
    ];

    protected $casts = [
        'porcentaje' => 'float',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'permanente' => 'boolean',
        'activo' => 'boolean',
        'todas_agencias' => 'boolean',
        'canal_fisico' => 'boolean',
        'canal_web' => 'boolean',
        'canal_app' => 'boolean',
        'mostrar_en_ofertas' => 'boolean',
    ];

    protected $appends = ['estado'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promotion_product');
    }

    public function agencias(): BelongsToMany
    {
        return $this->belongsToMany(Agencia::class, 'promotion_agencia');
    }

    public function getEstadoAttribute(): string
    {
        if (!$this->activo) {
            return 'PAUSADA';
        }

        $ahora = now();
        if ($this->fecha_inicio && $this->fecha_inicio->isAfter($ahora)) {
            return 'PROGRAMADA';
        }
        if (!$this->permanente && $this->fecha_fin && $this->fecha_fin->isBefore($ahora)) {
            return 'FINALIZADA';
        }

        return 'ACTIVA';
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
