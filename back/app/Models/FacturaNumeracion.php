<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FacturaNumeracion extends Model
{
    protected $table = 'factura_numeraciones';

    protected $fillable = ['clave', 'ultimo_numero'];

    /**
     * Reserva el siguiente número de factura de forma atómica.
     * Bloquea la fila del contador hasta el commit de la transacción que
     * lo llama, de modo que dos ventas simultáneas se ponen en fila.
     */
    public static function siguiente(string $clave = 'factura'): int
    {
        return DB::transaction(function () use ($clave) {
            $numeracion = static::where('clave', $clave)->lockForUpdate()->first();

            if (!$numeracion) {
                static::insertOrIgnore([
                    'clave'         => $clave,
                    'ultimo_numero' => 0,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
                $numeracion = static::where('clave', $clave)->lockForUpdate()->first();
            }

            // Nunca por debajo de lo ya emitido (p. ej. facturas cargadas a mano).
            $siguiente = max((int) $numeracion->ultimo_numero, (int) Sales::max('numeroFactura')) + 1;

            $numeracion->ultimo_numero = $siguiente;
            $numeracion->save();

            return $siguiente;
        });
    }
}
