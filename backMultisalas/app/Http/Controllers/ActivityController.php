<?php

namespace App\Http\Controllers;

use App\Models\Cufd;
use App\Models\Leyenda;
use App\Models\Activity;
use App\Models\Medida;
use App\Models\Sale;
use App\Models\Servicio;
use App\Models\Message;
use App\Models\Event;
use App\Models\Motivo;
use App\Models\Document;
use App\Models\Documentsector;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Models\Cui;
use App\Models\Sector;
use App\Models\Tipopago;
use Illuminate\Http\Request;


class ActivityController extends Controller{
    function verificarFacturas(){
//        https://pilotosiatservicios.impuestos.gob.bo/v2/ServicioFacturacionCompraVenta
//        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:siat="https://siat.impuestos.gob.bo/">
//   <soapenv:Header/>
//   <soapenv:Body>
//      <siat:verificacionEstadoFactura>
//         <SolicitudServicioVerificacionEstadoFactura>
//            <codigoAmbiente>?</codigoAmbiente>
//            <codigoDocumentoSector>?</codigoDocumentoSector>
//            <codigoEmision>?</codigoEmision>
//            <codigoModalidad>?</codigoModalidad>
//            <!--Optional:-->
//            <codigoPuntoVenta>?</codigoPuntoVenta>
//            <codigoSistema>?</codigoSistema>
//            <codigoSucursal>?</codigoSucursal>
//            <cufd>?</cufd>
//            <cuis>?</cuis>
//            <nit>?</nit>
//            <tipoFacturaDocumento>?</tipoFacturaDocumento>
//            <cuf>?</cuf>
//         </SolicitudServicioVerificacionEstadoFactura>
//      </siat:verificacionEstadoFactura>
//   </soapenv:Body>
//</soapenv:Envelope>

        $client = new \SoapClient(env("URL_SIAT")."ServicioFacturacionCompraVenta?wsdl",  [
            'stream_context' => stream_context_create([
                'http' => [

                ]
            ]),
            'cache_wsdl' => WSDL_CACHE_NONE,
            'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
            'trace' => 1,
            'use' => SOAP_LITERAL,
            'style' => SOAP_DOCUMENT,
        ]);
        $cui = Cui::where('codigoPuntoVenta', 0)->where('codigoSucursal', 0)->where('fechaVigencia','>=', now());

        $cudf= Cufd::where('codigoPuntoVenta', 0)->where('codigoSucursal', 0)->where('fechaVigencia','>=', now());


        $sales = Sale::whereIn('id', [452968,
            453044,
            453104,
            453081,
            452500,
            452513,
            452522,
            453062,
            453103,
            452737,
            452844,
            453041,
            452699,
            452543,
            452586,
            452829,
            452955,
            452419,
            453092,
            452452,
            453011,
            452433,
            452569,
            452423,
            453031,
            452540,
            453002,
            452695,
            452704,
            452708,
            452964,
            452739,
            452736,
            452475,
            453085,
            452545,
            452566])->get();



        foreach ($sales as $sale){

            $result = $client->verificacionEstadoFactura([
                "SolicitudServicioVerificacionEstadoFactura"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoDocumentoSector"=>1,
                    "codigoEmision"=>1,
                    "codigoModalidad"=>1,
                    "codigoPuntoVenta"=>0,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>0,
                    "cufd"=>$cudf->first()->codigo,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                    "tipoFacturaDocumento"=>1,
                    "cuf"=>$sale->cuf
                ]
            ]);
//            if ($result->RespuestaServicioFacturacion->codigoDescripcion=="RECHAZADA"){
                echo  json_encode($result)."<br>". $sale->id." ".$sale->tipo;
                echo "<br>-----------------<br>";
//                echo $sale->id." ".$sale->tipo." <br>";
//            }
        }
    }
    public function index()
    {

        return response()->json([
            'activities' => Activity::all(),
            'documents' => Document::all(),
            'documentsectors' => Documentsector::all(),
            'events' => Event::all(),
            'leyendas' => Leyenda::all(),
            'medidas' => Medida::all(),
            'messages' => Message::all(),
            'motivos' => Motivo::all(),
            'sectors' => Sector::all(),
            'servicios' => Servicio::all(),
            'tipopagos' => Tipopago::all(),
        ], 200);
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
     * @param  \App\Http\Requests\StoreActivityRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreActivityRequest $request)
    {
        try {
            $cui=Cui::where('codigoPuntoVenta', $request->codigoPuntoVenta)->where('codigoSucursal', $request->codigoSucursal)->where('fechaVigencia','>=', now());
            if ($cui->count()==0){
                return response()->json(['message' => 'El CUI no existe'], 400);
            }
            Activity::where('codigoPuntoVenta', $request->codigoPuntoVenta)->where('codigoSucursal', $request->codigoSucursal)->delete();
            $client = new \SoapClient(env("URL_SIAT")."FacturacionSincronizacion?WSDL",  [
                'stream_context' => stream_context_create([
                    'http' => [
                        'header' => "apikey: TokenApi ".env('TOKEN'),
                    ]
                ]),
                'cache_wsdl' => WSDL_CACHE_NONE,
                'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
                'trace' => 1,
                'use' => SOAP_LITERAL,
                'style' => SOAP_DOCUMENT,
            ]);
            $result= $client->sincronizarActividades([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);

            Sector::where('codigoPuntoVenta', $request->codigoPuntoVenta)->where('codigoSucursal', $request->codigoSucursal)->delete();
            foreach ($result->RespuestaListaActividades->listaActividades as $actividad) {
                $activity = new Activity();
                $activity->codigoCaeb = $actividad->codigoCaeb;
                $activity->descripcion = $actividad->descripcion;
                $activity->tipoActividad = $actividad->tipoActividad;
                $activity->codigoPuntoVenta = $request->codigoPuntoVenta;
                $activity->codigoSucursal = $request->codigoSucursal;
                $activity->save();
            }
            $result= $client->sincronizarListaActividadesDocumentoSector([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
            foreach ($result->RespuestaListaActividadesDocumentoSector->listaActividadesDocumentoSector as $registro) {
                $sector = new Sector();
                $sector->codigoActividad=$registro->codigoActividad;
                $sector->codigoDocumentoSector=$registro->codigoDocumentoSector;
                $sector->tipoDocumentoSector=$registro->tipoDocumentoSector;
                $sector->codigoPuntoVenta = $request->codigoPuntoVenta;
                $sector->codigoSucursal = $request->codigoSucursal;
                $sector->save();
            }

            Leyenda::where('codigoPuntoVenta', $request->codigoPuntoVenta)->where('codigoSucursal', $request->codigoSucursal)->delete();

            $result= $client->sincronizarListaLeyendasFactura([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
            foreach ($result->RespuestaListaParametricasLeyendas->listaLeyendas as $l) {
                $leyenda = new Leyenda();
                $leyenda->codigoActividad = $l->codigoActividad;
                $leyenda->descripcionLeyenda = $l->descripcionLeyenda;
                $leyenda->codigoPuntoVenta = $request->codigoPuntoVenta;
                $leyenda->codigoSucursal = $request->codigoSucursal;
                $leyenda->save();
            }

            Message::where('codigoPuntoVenta', $request->codigoPuntoVenta)->where('codigoSucursal', $request->codigoSucursal)->delete();

            $result= $client->sincronizarListaMensajesServicios([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
            foreach ($result->RespuestaListaParametricas->listaCodigos as $l) {
                $mensaje = new Message();
                $mensaje->codigoClasificador = $l->codigoClasificador;
                $mensaje->descripcion = $l->descripcion;
                $mensaje->codigoPuntoVenta = $request->codigoPuntoVenta;
                $mensaje->codigoSucursal = $request->codigoSucursal;
                $mensaje->save();
            }

            Servicio::where('codigoPuntoVenta', $request->codigoPuntoVenta)->where('codigoSucursal', $request->codigoSucursal)->delete();

            $result= $client->sincronizarListaProductosServicios([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
            foreach ($result->RespuestaListaProductos->listaCodigos as $l) {
                $servicio = new Servicio();
                $servicio->codigoActividad = $l->codigoActividad;
                $servicio->codigoProducto = $l->codigoProducto;
                $servicio->descripcionProducto = $l->descripcionProducto;
                $servicio->codigoPuntoVenta = $request->codigoPuntoVenta;
                $servicio->codigoSucursal = $request->codigoSucursal;
                $servicio->save();
            }

            Event::where('codigoPuntoVenta', $request->codigoPuntoVenta)->where('codigoSucursal', $request->codigoSucursal)->delete();

            $result= $client->sincronizarParametricaEventosSignificativos([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
            foreach ($result->RespuestaListaParametricas->listaCodigos as $l) {
                $evento = new Event();
                $evento->codigoClasificador = $l->codigoClasificador;
                $evento->descripcion = $l->descripcion;
                $evento->codigoPuntoVenta = $request->codigoPuntoVenta;
                $evento->codigoSucursal = $request->codigoSucursal;
                $evento->save();
            }

            Motivo::where('codigoPuntoVenta', $request->codigoPuntoVenta)->where('codigoSucursal', $request->codigoSucursal)->delete();

            $result= $client->sincronizarParametricaMotivoAnulacion([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
            foreach ($result->RespuestaListaParametricas->listaCodigos as $l) {
                $motivo = new Motivo();
                $motivo->codigoClasificador = $l->codigoClasificador;
                $motivo->descripcion = $l->descripcion;
                $motivo->codigoPuntoVenta = $request->codigoPuntoVenta;
                $motivo->codigoSucursal = $request->codigoSucursal;
                $motivo->save();
            }
            //paieses
            $result= $client->sincronizarParametricaPaisOrigen([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
            //fecha y hora
            $result= $client->sincronizarFechaHora([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);

            Document::where('codigoPuntoVenta', $request->codigoPuntoVenta)->where('codigoSucursal', $request->codigoSucursal)->delete();

            $result= $client->sincronizarParametricaTipoDocumentoIdentidad([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
            foreach ($result->RespuestaListaParametricas->listaCodigos as $l) {
                $document = new Document();
                $document->codigoClasificador = $l->codigoClasificador;
                $document->descripcion = $l->descripcion;
                $document->codigoPuntoVenta = $request->codigoPuntoVenta;
                $document->codigoSucursal = $request->codigoSucursal;
                $document->save();
            }

            Documentsector::where('codigoPuntoVenta', $request->codigoPuntoVenta)->where('codigoSucursal', $request->codigoSucursal)->delete();

            $result= $client->sincronizarParametricaTipoDocumentoSector([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
            foreach ($result->RespuestaListaParametricas->listaCodigos as $l) {
                $documentsector = new Documentsector();
                $documentsector->codigoClasificador = $l->codigoClasificador;
                $documentsector->descripcion = $l->descripcion;
                $documentsector->codigoPuntoVenta = $request->codigoPuntoVenta;
                $documentsector->codigoSucursal = $request->codigoSucursal;
                $documentsector->save();
            }

            $result= $client->sincronizarParametricaTipoEmision([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);

            $result= $client->sincronizarParametricaTipoHabitacion([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);

            Tipopago::where('codigoPuntoVenta', $request->codigoPuntoVenta)->where('codigoSucursal', $request->codigoSucursal)->delete();

            $result= $client->sincronizarParametricaTipoMetodoPago([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
            foreach ($result->RespuestaListaParametricas->listaCodigos as $l) {
                $tipopago = new Tipopago();
                $tipopago->codigoClasificador = $l->codigoClasificador;
                $tipopago->descripcion = $l->descripcion;
                $tipopago->codigoPuntoVenta = $request->codigoPuntoVenta;
                $tipopago->codigoSucursal = $request->codigoSucursal;
                $tipopago->save();
            }
            //BOLIVIANO DOLAR
            $result= $client->sincronizarParametricaTipoMoneda([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
//               <codigoClasificador>1</codigoClasificador>
//               <descripcion>PUNTO VENTA COMISIONISTA</descripcion>
//               <codigoClasificador>2</codigoClasificador>
//               <descripcion>PUNTO VENTA VENTANILLA DE COBRANZA</descripcion>
//               <codigoClasificador>3</codigoClasificador>
//               <descripcion>PUNTO DE VENTA MOVILES</descripcion>
//               <codigoClasificador>4</codigoClasificador>
//               <descripcion>PUNTO DE VENTA YPFB</descripcion>
//               <codigoClasificador>5</codigoClasificador>
//               <descripcion>PUNTO DE VENTA CAJEROS</descripcion>
//               <codigoClasificador>6</codigoClasificador>
//               <descripcion>PUNTO DE VENTA CONJUNTA</descripcion>
            $result= $client->sincronizarParametricaTipoPuntoVenta([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
            //              <codigoClasificador>1</codigoClasificador>
            //               <descripcion>FACTURA CON DERECHO A CREDITO FISCAL</descripcion>
            //               <codigoClasificador>2</codigoClasificador>
            //               <descripcion>FACTURA SIN DERECHO A CREDITO FISCAL</descripcion>
            //               <codigoClasificador>3</codigoClasificador>
            //               <descripcion>DOCUMENTO DE AJUSTE</descripcion>
            //               <codigoClasificador>4</codigoClasificador>
            //               <descripcion>DOCUMENTO EQUIVALENTE</descripcion>
            $result= $client->sincronizarParametricaTiposFactura([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
            //unidades


            Medida::where('codigoPuntoVenta', $request->codigoPuntoVenta)->where('codigoSucursal', $request->codigoSucursal)->delete();

            $result= $client->sincronizarParametricaUnidadMedida([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);

            foreach ($result->RespuestaListaParametricas->listaCodigos as $l) {
                $medida = new Medida();
                $medida->codigoClasificador = $l->codigoClasificador;
                $medida->descripcion = $l->descripcion;
                $medida->codigoPuntoVenta = $request->codigoPuntoVenta;
                $medida->codigoSucursal = $request->codigoSucursal;
                $medida->save();
            }



            return response()->json(['success' => 'Actividades sincronizadas'], 200);
        }catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }

    }

    public function listleyenda(Request $request){
        return Leyenda::where('codigoActividad',$request->codigo)->get();
    }

    public function motivoanular(){
        return Motivo::all();
    }

    public function sss(StoreActivityRequest $request)
    {
        try {

                $cui=Cui::where('codigoPuntoVenta', $request->codigoPuntoVenta)->where('codigoSucursal', $request->codigoSucursal)->where('fechaVigencia','>=', now());
            if ($cui->count()==0){
                return response()->json(['message' => 'El CUI no existe'], 400);
            }

            $client = new \SoapClient(env("URL_SIAT")."FacturacionSincronizacion?WSDL",  [
                'stream_context' => stream_context_create([
                    'http' => [
                        'header' => "apikey: TokenApi ".env('TOKEN'),
                    ]
                ]),
                'cache_wsdl' => WSDL_CACHE_NONE,
                'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
                'trace' => 1,
                'use' => SOAP_LITERAL,
                'style' => SOAP_DOCUMENT,
            ]);

                $result = $client->sincronizarActividades([
                    "SolicitudSincronizacion" => [
                        "codigoAmbiente" => env('AMBIENTE'),
                        "codigoPuntoVenta" => $request->codigoPuntoVenta,
                        "codigoSistema" => env('CODIGO_SISTEMA'),
                        "codigoSucursal" => $request->codigoSucursal,
                        "cuis" => $cui->first()->codigo,
                        "nit" => env('NIT'),
                    ]
                ]);

                $result = $client->sincronizarListaActividadesDocumentoSector([
                    "SolicitudSincronizacion" => [
                        "codigoAmbiente" => env('AMBIENTE'),
                        "codigoPuntoVenta" => $request->codigoPuntoVenta,
                        "codigoSistema" => env('CODIGO_SISTEMA'),
                        "codigoSucursal" => $request->codigoSucursal,
                        "cuis" => $cui->first()->codigo,
                        "nit" => env('NIT'),
                    ]
                ]);


                $result= $client->sincronizarListaLeyendasFactura([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);

            $result= $client->sincronizarListaMensajesServicios([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);

            $result= $client->sincronizarListaProductosServicios([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);

            $result= $client->sincronizarParametricaEventosSignificativos([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);

            $result= $client->sincronizarParametricaMotivoAnulacion([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);

            //paieses
            $result= $client->sincronizarParametricaPaisOrigen([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
            //fecha y hora
            $result= $client->sincronizarFechaHora([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);

            $result= $client->sincronizarParametricaTipoDocumentoIdentidad([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);


            $result= $client->sincronizarParametricaTipoDocumentoSector([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);

            $result= $client->sincronizarParametricaTipoEmision([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
            $result= $client->sincronizarParametricaTipoHabitacion([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);

            $result= $client->sincronizarParametricaTipoMetodoPago([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
            //BOLIVIANO DOLAR
            $result= $client->sincronizarParametricaTipoMoneda([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
//               <codigoClasificador>1</codigoClasificador>
//               <descripcion>PUNTO VENTA COMISIONISTA</descripcion>
//               <codigoClasificador>2</codigoClasificador>
//               <descripcion>PUNTO VENTA VENTANILLA DE COBRANZA</descripcion>
//               <codigoClasificador>3</codigoClasificador>
//               <descripcion>PUNTO DE VENTA MOVILES</descripcion>
//               <codigoClasificador>4</codigoClasificador>
//               <descripcion>PUNTO DE VENTA YPFB</descripcion>
//               <codigoClasificador>5</codigoClasificador>
//               <descripcion>PUNTO DE VENTA CAJEROS</descripcion>
//               <codigoClasificador>6</codigoClasificador>
//               <descripcion>PUNTO DE VENTA CONJUNTA</descripcion>
            $result= $client->sincronizarParametricaTipoPuntoVenta([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
            //              <codigoClasificador>1</codigoClasificador>
            //               <descripcion>FACTURA CON DERECHO A CREDITO FISCAL</descripcion>
            //               <codigoClasificador>2</codigoClasificador>
            //               <descripcion>FACTURA SIN DERECHO A CREDITO FISCAL</descripcion>
            //               <codigoClasificador>3</codigoClasificador>
            //               <descripcion>DOCUMENTO DE AJUSTE</descripcion>
            //               <codigoClasificador>4</codigoClasificador>
            //               <descripcion>DOCUMENTO EQUIVALENTE</descripcion>
            $result= $client->sincronizarParametricaTiposFactura([
                "SolicitudSincronizacion"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=>$cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
            //unidades


                $result = $client->sincronizarParametricaUnidadMedida([
                    "SolicitudSincronizacion" => [
                        "codigoAmbiente" => env('AMBIENTE'),
                        "codigoPuntoVenta" => $request->codigoPuntoVenta,
                        "codigoSistema" => env('CODIGO_SISTEMA'),
                        "codigoSucursal" => $request->codigoSucursal,
                        "cuis" => $cui->first()->codigo,
                        "nit" => env('NIT'),
                    ]
                ]);

            return response()->json(['success' => 'Actividades sincronizadas'], 200);
        }catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }

    }

    public function verificarComunicacion(){
        $client = new \SoapClient(env("URL_SIAT")."ServicioFacturacionCompraVenta?wsdl",  [
            'stream_context' => stream_context_create([
                'http' => [

                ]
            ]),
            'cache_wsdl' => WSDL_CACHE_NONE,
            'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
            'trace' => 1,
            'use' => SOAP_LITERAL,
            'style' => SOAP_DOCUMENT,
        ]);

            $result = $client->verificarComunicacion();
            return $result->return->transaccion;
    }

     /**
     * Display the specified resource.
     *
     * @param  \App\Models\Activity  $activity
     * @return \Illuminate\Http\Response
     */
    public function show(Activity $activity)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Activity  $activity
     * @return \Illuminate\Http\Response
     */
    public function edit(Activity $activity)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateActivityRequest  $request
     * @param  \App\Models\Activity  $activity
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateActivityRequest $request, Activity $activity)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Activity  $activity
     * @return \Illuminate\Http\Response
     */
    public function destroy(Activity $activity)
    {
        //
    }
}
