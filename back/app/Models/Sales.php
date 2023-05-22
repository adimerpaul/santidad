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
        "user_id",
        "cufd_id",
        "client_id",
    ];
    public function details(){ return $this->hasMany(Detail::class, 'sale_id'); }
    public function client(){ return $this->belongsTo(Client::class); }
}
