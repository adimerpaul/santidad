<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sales extends Model
{
    use HasFactory;
    protected $fillable = [
        "numeroFactura",
        "cuf",
        "cufd",
        "cui",
        "codigoSucursal",
        "codigoPuntoVenta",
        "fechaEmision",
        "montoTotal",
        "usuario",
        "concepto",
        "codigoRecepcion",
        "siatEnviado",
        "codigoRecepcionEventoSignificativo",
        "siatAnulado",
        "tipoVenta",
        "metodoPago",
        "codigoDocumentoSector",
        "leyenda",
        "venta",
        "aporte",
        "qr",
        "user_id",
        "cufd_id",
        "client_id",
        "agencia_id",
        "modificado",
    ];
    public function details(){ return $this->hasMany(Detail::class, 'sale_id'); }
    public function client(){ return $this->belongsTo(Client::class); }
    public function user(){ return $this->belongsTo(User::class); }
    public function agencia(){ return $this->belongsTo(Agencia::class); }
}
