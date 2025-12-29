<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Factura extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero_factura',
        'proveedor',
        'vendedor',
        'fecha_compra',
        'monto_total',
        'tipo_pago',
        'metodo_pago',
        'fecha_vencimiento',
        'estado',
        'pagado',
        'agencia_id',
        'proveedor_id',
        'observaciones',
        'detalle_compras',
        'user_id',
        'numero_transaccion'
    ];

    protected $casts = [
        'fecha_compra' => 'date',
        'fecha_vencimiento' => 'date',
        'monto_total' => 'decimal:2',
        'pagado' => 'decimal:2',
        'detalle_compras' => 'array'
    ];

    // Relaciones
    public function agencia()
    {
        return $this->belongsTo(Agencia::class);
    }

    public function proveedorRelacion()
    {
        return $this->belongsTo(Client::class, 'proveedor_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pagos()
    {
        return $this->hasMany(PagoFactura::class);
    }

    public function buys()
    {
        return $this->hasMany(Buy::class, 'factura_id');
    }

    // Métodos de ayuda
    public function getSaldoAttribute()
    {
        return $this->monto_total - $this->pagado;
    }

    public function getEstadoColorAttribute()
    {
        return match($this->estado) {
            'Pagado' => 'positive',
            'Parcial' => 'warning',
            'Pendiente' => 'negative',
            default => 'grey'
        };
    }

    public function registrarPago($monto, $metodo = null, $referencia = null, $vendedor = null, $observaciones = null)
    {
        $pago = new PagoFactura([
            'monto' => $monto,
            'fecha_pago' => now()->toDateString(),
            'vendedor' => $vendedor,
            'metodo_pago' => $metodo,
            'referencia' => $referencia,
            'observaciones' => $observaciones,
            'user_id' => auth()->id()
        ]);

        $this->pagado += $monto;

        // Actualizar estado
        if ($this->pagado >= $this->monto_total) {
            $this->estado = 'Pagado';
        } elseif ($this->pagado > 0) {
            $this->estado = 'Parcial';
        }

        $this->save();
        $this->pagos()->save($pago);

        return $pago;
    }
}
