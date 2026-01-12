<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PagoFactura extends Model
{
    use HasFactory, SoftDeletes;
//    $name = 'pago_facturas';
    protected $table = 'pagos_factura';

    protected $fillable = [
        'factura_id',
        'monto',
        'fecha_pago',
        'vendedor',
        'metodo_pago',
        'referencia',
        'observaciones',
        'user_id'
    ];

    protected $casts = [
        'fecha_pago' => 'datetime',
        'monto' => 'decimal:2'
    ];

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
