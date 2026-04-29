<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rental extends Model
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
"periodoFacturado",
"codigoRecepcion",
"siatEnviado",
"codigoRecepcionEventoSignificativo",
"siatAnulado",
"codigoDocumentoSector",
"actividadEconomica",
"codigoProductoSin",
"codigoProducto",
"descripcion",
"cantidad",
"unidadMedida",
"precioUnitario",
"montoDescuento",
"subTotal",
"user_id",
"cufd_id",
"client_id"];

    public function user(){
            return $this->belongsTo(User::class);
    }
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

}
