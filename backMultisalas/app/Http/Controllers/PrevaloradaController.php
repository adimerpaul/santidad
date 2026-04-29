<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\Client;
use App\Models\Cufd;
use App\Models\Cui;
use App\Models\Leyenda;
use App\Models\Prevalorada;
use App\Http\Requests\StorePrevaloradaRequest;
use App\Http\Requests\UpdatePrevaloradaRequest;
use App\Models\Rental;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SimpleXMLElement;
use DOMDocument;
class PrevaloradaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }
    public function prevaloradaConsulta(Request $request)
    {
        $rental = Prevalorada::whereDate('fechaEmision','>=', $request->fechaInicio)
            ->whereDate('fechaEmision','<=', $request->fechaFin)
            ->with('user')
            ->get();
        return response()->json($rental);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StorePrevaloradaRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePrevaloradaRequest $request)
    {
        //return $request->vehiculo['descripcion'];
//        if (Client::where('complemento',$request->client['complemento'])->where('numeroDocumento',$request->client['numeroDocumento'])->count()==1) {
//            $client=Client::find($request->client['id']);
//            $client->nombreRazonSocial=$request->client['nombreRazonSocial'];
//            $client->codigoTipoDocumentoIdentidad=$request->client['codigoTipoDocumentoIdentidad'];
//            $client->save();
//        }else if(Client::where('numeroDocumento',$request->client['numeroDocumento'])->count()==1){
//            $client=Client::find($request->client['id']);
//            $client->nombreRazonSocial=$request->client['nombreRazonSocial'];
//            $client->codigoTipoDocumentoIdentidad=$request->client['codigoTipoDocumentoIdentidad'];
//            $client->save();
//        }else{
//            $client=new Client();
//            $client->nombreRazonSocial=$request->client['nombreRazonSocial'];
//            $client->codigoTipoDocumentoIdentidad=$request->client['codigoTipoDocumentoIdentidad'];
//            $client->numeroDocumento=$request->client['numeroDocumento'];
//            $client->complemento=$request->client['complemento'];
//            $client->save();
//        }

        $codigoAmbiente=env('AMBIENTE');
        $codigoDocumentoSector=23; // 1 compraventa 2 alquiler 23 prevaloradas
        $codigoEmision=1; // 1 online 2 offline 3 masivo
        $codigoModalidad=env('MODALIDAD'); //1 electronica 2 computarizada
        $codigoPuntoVenta=0;
        $codigoSistema=env('CODIGO_SISTEMA');
        $tipoFacturaDocumento=1; // 1 con credito fiscal 2 sin creditofical 3 nota debito credito

        $codigoSucursal=0;

        $user=User::find($request->user()->id);
        $retorno=[];

        if (Cui::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->count()==0){
            return response()->json(['message' => 'No existe CUI para la venta!!'], 500);
        }
        if (Cufd::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->count()==0){
            return response()->json(['message' => 'No exite CUFD para la venta!!'], 500);
        }
        $cui=Cui::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->first();
        $cufd=Cufd::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->first();

        // repetir
        for ($i=0;$i<$request->cantidad;$i++) {
            if (Prevalorada::where('cufd', $cufd->codigo)->count() == 0) {
                $numeroFactura = 1;
            } else {
                $sale = Prevalorada::where('cufd', $cufd->codigo)->orderBy('numeroFactura', 'desc')->first();
                $numeroFactura = intval($sale->numeroFactura) + 1;
            }

            $sale = new Prevalorada();
            $unidadMedida = 62;
            $actividadEconomica = 522000;
            $codigoProductoSin = 67430;
            $codigoProducto = "10101";
            $monto = $request->vehiculo['costo'];
            $descripcion = $request->vehiculo['descripcion'];

            $leyenda = Leyenda::where("codigoActividad", "522000")->get();
            $count = $leyenda->count();
            $leyenda = $leyenda[rand(0, $count - 1)]->descripcionLeyenda;

            $sale->numeroFactura = $numeroFactura;
            $sale->cuf = "";
            $sale->cufd = $cufd->codigo;
            $sale->cui = $cui->codigo;
            $sale->codigoSucursal = $codigoSucursal;
            $sale->codigoPuntoVenta = $codigoPuntoVenta;
            $sale->fechaEmision = now();
            $sale->montoTotal = $monto;
            $sale->usuario = $user->name;
//        $sale->periodoFacturado=$request->periodoFacturado;
            $sale->codigoDocumentoSector = $codigoDocumentoSector;
            $sale->actividadEconomica = $actividadEconomica;
            $sale->codigoProductoSin = $codigoProductoSin;
            $sale->codigoProducto = $codigoProducto;
            $sale->descripcion = $descripcion;
            $sale->cantidad = 1;
            $sale->unidadMedida = $unidadMedida;
            $sale->precioUnitario = $monto;
            $sale->montoDescuento = 0;
            $sale->subTotal = $monto;
            $sale->user_id = $user->id;
            $sale->cufd_id = $cufd->id;
            $sale->leyenda = $leyenda;
            $sale->placa='';
            $sale->save();


            $cuf = new CUF();
            //     * @param nit NIT emisor
//     * @param fh Fecha y Hora en formato yyyyMMddHHmmssSSS
//     * @param sucursal
//     * @param mod Modalidad
//     * @param temision Tipo de Emision
//     * @param cdf Codigo Documento Fiscal
//     * @param tds Tipo Documento Sector
//     * @param nf Numero de Factura
//     * @param pos Punto de Venta
            $fechaEnvio = date("Y-m-d\TH:i:s.000");
            $cuf = $cuf->obtenerCUF(env('NIT'), date("YmdHis000"), $codigoSucursal, $codigoModalidad, $codigoEmision, $tipoFacturaDocumento, $codigoDocumentoSector, $numeroFactura, $codigoPuntoVenta);
            $cuf = $cuf . $cufd->codigoControl;
//        $text="<?xml version='1.0' encoding='UTF-8' standalone='yes'
//<facturaElectronicaAlquilerBienInmueble xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'
//                                        xsi:noNamespaceSchemaLocation='facturaElectronicaAlquilerBienInmueble.xsd'>
//    <cabecera>
//        <nitEmisor>".env('NIT')."</nitEmisor>
//        <razonSocialEmisor>".env('RAZON')."</razonSocialEmisor>
//        <municipio>Oruro</municipio>
//        <telefono>".env('TELEFONO')."</telefono>
//        <numeroFactura>$numeroFactura</numeroFactura>
//        <cuf>$cuf</cuf>
//        <cufd>".$cufd->codigo."</cufd>
//        <codigoSucursal>$codigoSucursal</codigoSucursal>
//        <direccion>".env('DIRECCION')."</direccion>
//        <codigoPuntoVenta>$codigoPuntoVenta</codigoPuntoVenta>
//        <fechaEmision>$fechaEnvio</fechaEmision>
//        <nombreRazonSocial>".$client->nombreRazonSocial."</nombreRazonSocial>
//        <codigoTipoDocumentoIdentidad>".$client->codigoTipoDocumentoIdentidad."</codigoTipoDocumentoIdentidad>
//        <numeroDocumento>".$client->numeroDocumento."</numeroDocumento>
//        <complemento>".$client->complemento."</complemento>
//        <codigoCliente>".$client->id."</codigoCliente>
//        <periodoFacturado>".$request->periodoFacturado."</periodoFacturado>
//        <codigoMetodoPago>1</codigoMetodoPago>
//        <numeroTarjeta xsi:nil='true'/>
//        <montoTotal>".$request->montoTotal."</montoTotal>
//        <montoTotalSujetoIva>".$request->montoTotal."</montoTotalSujetoIva>
//        <codigoMoneda>1</codigoMoneda>
//        <tipoCambio>1</tipoCambio>
//        <montoTotalMoneda>".$request->montoTotal."</montoTotalMoneda>
//        <descuentoAdicional xsi:nil='true'/>
//        <codigoExcepcion>0</codigoExcepcion>
//        <cafc xsi:nil='true'/>
//        <leyenda>$leyenda</leyenda>
//        <usuario>".$user->name."</usuario>
//        <codigoDocumentoSector>2</codigoDocumentoSector>
//    </cabecera>
//    <detalle>
//        <actividadEconomica>681011</actividadEconomica>
//        <codigoProductoSin>72111</codigoProductoSin>
//        <codigoProducto>10101</codigoProducto>
//        <descripcion>".$request->descripcion."</descripcion>
//        <cantidad>1</cantidad>
//        <unidadMedida>$unidadMedida</unidadMedida>
//        <precioUnitario>".$request->montoTotal."</precioUnitario>
//        <montoDescuento>0</montoDescuento>
//        <subTotal>".$request->montoTotal."</subTotal>
//    </detalle>
//</facturaElectronicaAlquilerBienInmueble>";
            $text = "
        <facturaElectronicaPrevalorada xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:noNamespaceSchemaLocation='facturaElectronicaPrevalorada.xsd'>
<cabecera>
<nitEmisor>" . env('NIT') . "</nitEmisor>
<razonSocialEmisor>" . env('RAZON') . "</razonSocialEmisor>
<municipio>Oruro</municipio>
<telefono>" . env('TELEFONO') . "</telefono>
<numeroFactura>$numeroFactura</numeroFactura>
<cuf>$cuf</cuf>
<cufd>" . $cufd->codigo . "</cufd>
<codigoSucursal>$codigoSucursal</codigoSucursal>
<direccion>" . env('DIRECCION') . "</direccion>
<codigoPuntoVenta>$codigoPuntoVenta</codigoPuntoVenta>
<fechaEmision>$fechaEnvio</fechaEmision>
<nombreRazonSocial>S/N</nombreRazonSocial>
<codigoTipoDocumentoIdentidad>5</codigoTipoDocumentoIdentidad>
<numeroDocumento>0</numeroDocumento>
<codigoCliente>N/A</codigoCliente>
<codigoMetodoPago>1</codigoMetodoPago>
<numeroTarjeta xsi:nil='true'/>
<montoTotal>" . $monto . "</montoTotal>
<montoTotalSujetoIva>" . $monto . "</montoTotalSujetoIva>
<codigoMoneda>1</codigoMoneda>
<tipoCambio>1</tipoCambio>
<montoTotalMoneda>" . $monto . "</montoTotalMoneda>
<leyenda>$leyenda</leyenda>
<usuario>" . $user->name . "</usuario>
<codigoDocumentoSector>23</codigoDocumentoSector>
</cabecera>
<detalle>
<actividadEconomica>$actividadEconomica</actividadEconomica>
<codigoProductoSin>$codigoProductoSin</codigoProductoSin>
<codigoProducto>$codigoProducto</codigoProducto>
<descripcion>" . $descripcion . "</descripcion>
<cantidad>1</cantidad>
<unidadMedida>$unidadMedida</unidadMedida>
<precioUnitario>" . $monto . "</precioUnitario>
<montoDescuento>0</montoDescuento>
<subTotal>" . $monto . "</subTotal>
</detalle>
</facturaElectronicaPrevalorada>";

            $xml = new SimpleXMLElement($text);
            $dom = new DOMDocument('1.0');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml->asXML());
            $nameFile = $sale->id;
            $dom->save("rentals/" . $nameFile . '.xml');

            $firmar = new Firmar();
            $firmar->firmar("rentals/" . $nameFile . '.xml');


            $xml = new DOMDocument();
            $xml->load("rentals/" . $nameFile . '.xml');
            if (!$xml->schemaValidate('./facturaElectronicaPrevalorada.xsd')) {
                return "invalid";
            } else {
//            return "validated";
            }
//        exit;


            $file = "rentals/" . $nameFile . '.xml';
            $gzfile = "rentals/" . $nameFile . '.xml' . '.gz';
            $fp = gzopen($gzfile, 'w9');
            gzwrite($fp, file_get_contents($file));
            gzclose($fp);
//        unlink($file);


            $archivo = $firmar->getFileGzip("rentals/" . $nameFile . '.xml' . '.gz');
            $hashArchivo = hash('sha256', $archivo);
            unlink($gzfile);
//        return $request;
//        exit;


            try {
                $client = new \SoapClient(env("URL_SIAT") . "ServicioFacturacionElectronica?WSDL", [
                    'stream_context' => stream_context_create([
                        'http' => [
                            'header' => "apikey: TokenApi " . env('TOKEN'),
                        ]
                    ]),
                    'cache_wsdl' => WSDL_CACHE_NONE,
                    'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
                    'trace' => 1,
                    'use' => SOAP_LITERAL,
                    'style' => SOAP_DOCUMENT,
                ]);
                $result = $client->recepcionFactura([
                    "SolicitudServicioRecepcionFactura" => [
                        "codigoAmbiente" => $codigoAmbiente,
                        "codigoDocumentoSector" => $codigoDocumentoSector,
                        "codigoEmision" => $codigoEmision,
                        "codigoModalidad" => $codigoModalidad,
                        "codigoPuntoVenta" => $codigoPuntoVenta,
                        "codigoSistema" => $codigoSistema,
                        "codigoSucursal" => $codigoSucursal,
                        "cufd" => $cufd->codigo,
                        "cuis" => $cui->codigo,
                        "nit" => env('NIT'),
                        "tipoFacturaDocumento" => $tipoFacturaDocumento,
                        "archivo" => $archivo,
                        "fechaEnvio" => $fechaEnvio,
                        "hashArchivo" => $hashArchivo,
                    ]
                ]);
//            return var_dump($result);
//            exit;
                $sale->siatEnviado = true;
                $sale->codigoRecepcion = $result->RespuestaServicioFacturacion->codigoRecepcion;
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage(),'resp'=>$result->RespuestaServicioFacturacion]);
                $sale->siatEnviado = false;
            }

//        return $result;
//        if (isset($result->RespuestaServicioFacturacion)){
//
//        }else{
//
//        }

            $sale->cuf = $cuf;
//
            $sale->save();
            array_push($retorno,$sale);
        }
        return response()->json($retorno);

    }
    public function anularPrev(Request $request){
        //return $request->sale['id'];
        $codigoAmbiente=env('AMBIENTE');
        $codigoDocumentoSector=23; // 1 compraventa 2 alquiler 23 prevaloradas
        $codigoEmision=1; // 1 online 2 offline 3 masivo
        $codigoModalidad=env('MODALIDAD'); //1 electronica 2 computarizada
        $codigoPuntoVenta=0;
        $codigoSistema=env('CODIGO_SISTEMA');
        $tipoFacturaDocumento=1; // 1 con credito fiscal 2 sin creditofical 3 nota debito credito
        $codigoSucursal=0;
        $nit=ENV('NIT');

        $user=User::find($request->user()->id);

        if (Cui::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->count()==0){
            return response()->json(['message' => 'No existe CUI para la venta!!'], 400);
        }
        if (Cufd::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->count()==0){
            return response()->json(['message' => 'No exite CUFD para la venta!!'], 400);
        }
        $cui=Cui::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->first();
        $cufd=Cufd::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->first();

        //codigomotivo
        //cuf

        try {
            $client = new \SoapClient(env("URL_SIAT")."ServicioFacturacionCompraVenta?WSDL",  [
                'stream_context' => stream_context_create([
                    'http' => [
                        'header' => "apikey: TokenApi " . env('TOKEN'),
                    ]
                ]),
                'cache_wsdl' => WSDL_CACHE_NONE,
                'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
                'trace' => 1,
                'use' => SOAP_LITERAL,
                'style' => SOAP_DOCUMENT,
            ]);
            $result= $client->anulacionFactura([
                "SolicitudServicioAnulacionFactura"=>[
                    "codigoAmbiente"=>$codigoAmbiente,
                    "codigoDocumentoSector"=>$codigoDocumentoSector,
                    "codigoEmision"=>$codigoEmision,
                    "codigoModalidad"=>$codigoModalidad,
                    "codigoPuntoVenta"=>$codigoPuntoVenta,
                    "codigoSistema"=>$codigoSistema,
                    "codigoSucursal"=>$codigoSucursal,
                    "cufd"=>$request->sale['cufd'],
                    "cuis"=>$request->sale['cui'],
                    "nit"=>env('NIT'),
                    "tipoFacturaDocumento"=>$tipoFacturaDocumento,
                    "codigoMotivo"=>$request->motivo['codigoClasificador'],
                    "cuf"=>$request->sale['cuf']
                ]
            ]);
            if($result->RespuestaServicioFacturacion->transaccion){
                $sale=Sale::find($request->sale['id']);
                $sale->siatAnulado=1;
                $sale->save();
            }
        }catch (\Exception $e) {
//            return response()->json(['error' => $e->getMessage()]);
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Prevalorada  $prevalorada
     * @return \Illuminate\Http\Response
     */
    public function show(Prevalorada $prevalorada)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Prevalorada  $prevalorada
     * @return \Illuminate\Http\Response
     */
    public function edit(Prevalorada $prevalorada)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePrevaloradaRequest  $request
     * @param  \App\Models\Prevalorada  $prevalorada
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePrevaloradaRequest $request, Prevalorada $prevalorada)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Prevalorada  $prevalorada
     * @return \Illuminate\Http\Response
     */
    public function destroy(Prevalorada $prevalorada)
    {
        //
    }
}
