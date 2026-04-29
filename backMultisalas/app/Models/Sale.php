<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;
    protected $fillable = [
        'numeroFactura',
        'cuf',
        'cufd',
        'cui',
        'codigoSucursal',
        'codigoPuntoVenta',
        'fechaEmision',
        'montoTotal',
        'usuario',
        'codigoRecepcion',
        'siatEnviado',
        'codigoRecepcionEventoSignificativo',
        'siatAnulado',
        'tipo',
        'codigoDocumentoSector',
        'leyenda',
        'vip',
        'cortesia',
        'credito',
        'pagoQr',
        'qrId',
        'qrTransactionId',
        'qrPagadoAt',
        'user_id',
        'cufd_id',
        'client_id',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function details()
    {
        return $this->hasMany(Detail::class);
    }
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
