<?php

namespace App\Http\Controllers;

use App\Mail\TestMail;
use App\Models\Client;
use App\Models\Cortesia;
use App\Models\Cufd;
use App\Models\Cui;
use App\Models\Detail;
use App\Models\Leyenda;
use App\Models\Momentaneo;
use App\Models\Programa;
use App\Models\Rental;
use App\Models\Sale;
use App\Models\SaleCandy;
use App\Http\Requests\StoreSaleCandyRequest;
use App\Http\Requests\UpdateSaleCandyRequest;
use App\Models\Ticket;
use App\Models\User;
use DOMDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use SimpleXMLElement;

class SaleCandyController extends Controller
{
    private function applyQrData(Sale $sale, Request $request): void
    {
        $sale->pagoQr = $request->boolean('pagoQr');
        $sale->qrId = $request->input('qrId');
        $sale->qrTransactionId = $request->input('qrTransactionId');
        $sale->qrPagadoAt = $request->boolean('pagoQr') ? now() : null;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     * @param  \App\Http\Requests\StoreSaleCandyRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSaleCandyRequest $request)
    {   // verioficar detalleVenta que no este vacio
        if (sizeof($request->detalleVenta) == 0) {
            return response()->json(['message' => 'El detalle de la venta no puede estar vacio'], 400);
        }

        if ($request->client['complemento']==null){
            $complemento="";
        }else{
            $complemento=$request->client['complemento'];
        }
        if ( $complemento!= "" && Client::whereComplemento($complemento)->where('numeroDocumento',$request->client['numeroDocumento'])->count()==1) {
            $client=Client::find($request->client['id']);
//            $client->nombreRazonSocial=strtoupper($request->client['nombreRazonSocial']);
            $client->nombreRazonSocial=($request->client['nombreRazonSocial']);
            $client->codigoTipoDocumentoIdentidad=$request->client['codigoTipoDocumentoIdentidad'];
            $client->email=$request->client['email'];
            $client->save();
//            return "actualizado con complento";
        }else if(Client::where('numeroDocumento',$request->client['numeroDocumento'])->whereComplemento($complemento)->count()){
            $client=Client::find($request->client['id']);
//            $client->nombreRazonSocial=strtoupper($request->client['nombreRazonSocial']);
            $client->nombreRazonSocial=($request->client['nombreRazonSocial']);
            $client->codigoTipoDocumentoIdentidad=$request->client['codigoTipoDocumentoIdentidad'];
            $client->email=$request->client['email'];
            $client->save();
//            return "actualizado";
        }else{
            $client=new Client();
            $client->nombreRazonSocial=strtoupper($request->client['nombreRazonSocial']);
            $client->codigoTipoDocumentoIdentidad=$request->client['codigoTipoDocumentoIdentidad'];
            $client->numeroDocumento=$request->client['numeroDocumento'];
            $client->complemento=strtoupper($request->client['complemento']);
            $client->email=$request->client['email'];
            $client->save();
//            return "nuevo";
        }

        if ($request->vip=="SI"){
            return $this->insertarVip($request,$client);
        }

        if ($request->client['numeroDocumento']=="0"){
            return $this->insertarRecibo($request,$client);
        }

        if (sizeof($request->detalleVenta) > 0){


        $codigoAmbiente=env('AMBIENTE');
        $codigoDocumentoSector=1; // 1 compraventa 2 alquiler 23 prevaloradas
        $codigoEmision=1; // 1 online 2 offline 3 masivo
        $codigoModalidad=env('MODALIDAD'); //1 electronica 2 computarizada
        $codigoPuntoVenta=0;
        $codigoSistema=env('CODIGO_SISTEMA');
        $tipoFacturaDocumento=1; // 1 con credito fiscal 2 sin creditofical 3 nota debito credito

        $codigoSucursal=0;
        error_log("request: ".json_encode($request->all()));

        $user=User::find($request->user()->id);

        if (Cui::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->count()==0){
            return response()->json(['message' => 'No existe CUI para la venta!!'], 400);
        }
        if (Cufd::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->count()==0){
            return response()->json(['message' => 'No exite CUFD para la venta!!'], 400);
        }
        $cui=Cui::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->first();
        $cufd=Cufd::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->first();

        error_log("cui: ".json_encode($cui));
        if (Sale::where('cufd', $cufd->codigo)->where('tipo','CANDY')->count()==0){
            $numeroFactura=1;
        }else{
//            $sale=Sale::where('cufd',$cufd->codigo)->where('tipo','CANDY')->orderBy('numeroFactura', 'desc')->first();
            $max=Sale::where('cufd',$cufd->codigo)->where('tipo','CANDY')->max('numeroFactura');
            $numeroFactura=intval($max)+1;
        }
        error_log("numeroFactura A: ".$numeroFactura);
//        if (count(Sale::all())==0) { cambiar por count
        if (Sale::count()==0) {
            $saleId=1;
        }else{
            $sale=Sale::orderBy('id', 'desc')->first();
            $saleId=$sale->id+1;
        }
        error_log("numeroFactura B: ".$numeroFactura);
        $detalleFactura="";
        foreach ($request->detalleVenta as $detalle){
            $detalleFactura.="<detalle>
                <actividadEconomica>590000</actividadEconomica>
                <codigoProductoSin>99100</codigoProductoSin>
                <codigoProducto>".$detalle['product_id']."</codigoProducto>
                <descripcion>".$detalle['nombre']."</descripcion>
                <cantidad>".$detalle['cantidad']."</cantidad>
                <unidadMedida>62</unidadMedida>
                <precioUnitario>".$detalle['precio']."</precioUnitario>
                <montoDescuento>0</montoDescuento>
                <subTotal>".$detalle['subtotal']."</subTotal>
                <numeroSerie xsi:nil='true'/>
                <numeroImei xsi:nil='true'/>
            </detalle>";
        }
//
//        Ticket::insert($data);
//
//        Detail::insert($dataDetail);


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
        $leyenda=Leyenda::where("codigoActividad","590000")->get();
        error_log("leyenda: ".json_encode($leyenda));
        $count=$leyenda->count();
        $leyenda=$leyenda[rand(0,$count-1)]->descripcionLeyenda;

        $fechaEnvio=date("Y-m-d\TH:i:s.000");

        $cuf = $cuf->obtenerCUF(env('NIT'), date("YmdHis000"), $codigoSucursal, $codigoModalidad, $codigoEmision, $tipoFacturaDocumento, $codigoDocumentoSector, $numeroFactura, $codigoPuntoVenta);
        $cuf=$cuf.$cufd->codigoControl;
        $text="<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<facturaElectronicaCompraVenta xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:noNamespaceSchemaLocation='facturaElectronicaCompraVenta.xsd'>    <cabecera>
        <nitEmisor>".env('NIT')."</nitEmisor>
        <razonSocialEmisor>".env('RAZON')."</razonSocialEmisor>
        <municipio>Oruro</municipio>
        <telefono>".env('TELEFONO')."</telefono>
        <numeroFactura>$numeroFactura</numeroFactura>
        <cuf>$cuf</cuf>
        <cufd>".$cufd->codigo."</cufd>
        <codigoSucursal>$codigoSucursal</codigoSucursal>
        <direccion>".env('DIRECCION')."</direccion>
        <codigoPuntoVenta>$codigoPuntoVenta</codigoPuntoVenta>
        <fechaEmision>$fechaEnvio</fechaEmision>
        <nombreRazonSocial>".str_replace("&","&amp;",trim($client->nombreRazonSocial)) ."</nombreRazonSocial>
        <codigoTipoDocumentoIdentidad>".$client->codigoTipoDocumentoIdentidad."</codigoTipoDocumentoIdentidad>
        <numeroDocumento>".$client->numeroDocumento."</numeroDocumento>
        <complemento>".$client->complemento."</complemento>
        <codigoCliente>".$client->id."</codigoCliente>
        <codigoMetodoPago>1</codigoMetodoPago>
        <numeroTarjeta xsi:nil='true'/>
        <montoTotal>".$request->montoTotal."</montoTotal>
        <montoTotalSujetoIva>".$request->montoTotal."</montoTotalSujetoIva>
        <codigoMoneda>1</codigoMoneda>
        <tipoCambio>1</tipoCambio>
        <montoTotalMoneda>".$request->montoTotal."</montoTotalMoneda>
        <montoGiftCard xsi:nil='true'/>
        <descuentoAdicional>0</descuentoAdicional>
        <codigoExcepcion>".($client->codigoTipoDocumentoIdentidad==5?1:0)."</codigoExcepcion>
        <cafc xsi:nil='true'/>
        <leyenda>$leyenda</leyenda>
        <usuario>".explode(" ", $user->name)[0]."</usuario>
        <codigoDocumentoSector>".$codigoDocumentoSector."</codigoDocumentoSector>
        </cabecera>";
        $text.=$detalleFactura;
        $text.="</facturaElectronicaCompraVenta>";

        $xml = new SimpleXMLElement($text);
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        $nameFile=$saleId;
        $dom->save("archivos/".$nameFile.'.xml');

        $firmar = new Firmar();
        $firmar->firmar("archivos/".$nameFile.'.xml');


        $xml = new DOMDocument();
        $xml->load("archivos/".$nameFile.'.xml');
        if (!$xml->schemaValidate('./facturaElectronicaCompraVenta.xsd')) {
            echo "invalid";
        }
        else {
//            echo "validated";
        }
//        exit;


        $file = "archivos/".$nameFile.'.xml';
        $gzfile = "archivos/".$nameFile.'.xml'.'.gz';
        $fp = gzopen ($gzfile, 'w9');
        gzwrite ($fp, file_get_contents($file));
        gzclose($fp);

        $archivo=$firmar->getFileGzip("archivos/".$nameFile.'.xml'.'.gz');
        $hashArchivo=hash('sha256', $archivo);
//        unlink($gzfile);
        try {
            error_log('entro try');
            $clientSoap = new \SoapClient(env("URL_SIAT")."ServicioFacturacionCompraVenta?WSDL",  [
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
            error_log('despues client');
            $result= $clientSoap->recepcionFactura([
                "SolicitudServicioRecepcionFactura"=>[
                    "codigoAmbiente"=>$codigoAmbiente,
                    "codigoDocumentoSector"=>$codigoDocumentoSector,
                    "codigoEmision"=>$codigoEmision,
                    "codigoModalidad"=>$codigoModalidad,
                    "codigoPuntoVenta"=>$codigoPuntoVenta,
                    "codigoSistema"=>$codigoSistema,
                    "codigoSucursal"=>$codigoSucursal,
                    "cufd"=>$cufd->codigo,
                    "cuis"=>$cui->codigo,
                    "nit"=>env('NIT'),
                    "tipoFacturaDocumento"=>$tipoFacturaDocumento,
                    "archivo"=>$archivo,
                    "fechaEnvio"=>$fechaEnvio,
                    "hashArchivo"=>$hashArchivo,
                ]
            ]);
            error_log("resultcandy ".json_encode($result));
            if ($result->RespuestaServicioFacturacion->transaccion) {
                $sale=new Sale();
                $sale->numeroFactura=$numeroFactura;
                $sale->cuf="";
                $sale->cufd=$cufd->codigo;
                $sale->cui=$cui->codigo;
                $sale->codigoSucursal=$codigoSucursal;
                $sale->codigoPuntoVenta=$codigoPuntoVenta;
                $sale->fechaEmision=now();
                $sale->montoTotal=$request->montoTotal;
                $sale->usuario=$user->name;
                $sale->codigoDocumentoSector=$codigoDocumentoSector;
                $sale->user_id=$user->id;
                $sale->cufd_id=$cufd->id;
                $sale->client_id=$client->id;
                $sale->tipo="CANDY";
                $sale->leyenda=$leyenda;
                $sale->vip=$request->vip;
                $sale->credito=$request->tarjeta;
                $this->applyQrData($sale, $request);
                $sale->save();

                error_log("sale candy: ".json_encode($sale));

                try {
                    if ($request->client['email']!='' && $request->client['email']!=null ){
                        $details=[
                            "title"=>"Factura",
                            "body"=>"Gracias por su compra",
                            "online"=>true,
                            "anulado"=>false,
                            "habilitar" => false,
                            "cuf"=>"",
                            "numeroFactura"=>"",
                            "sale_id"=>$sale->id,
                            "carpeta"=>"archivos",
                        ];
                        Mail::to($request->client['email'])->send(new TestMail($details));
                    }
                }catch (\Exception $e){
                    error_log("error mail candy: ".$e->getMessage());
                }

                $dataDetail=[];
                foreach ($request->detalleVenta as $detalle){
                    $d=[
                        'actividadEconomica'=>"590000",
                        'codigoProductoSin'=>"99100",
                        'cantidad'=>$detalle['cantidad'],
                        'precioUnitario'=>$detalle['precio'],
                        'subTotal'=>$detalle['subtotal'],
                        'sale_id'=>$sale->id,
//                        'programa_id'=>$detalle['programa_id'],
                        'product_id'=>$detalle['product_id'],
                        'descripcion'=>$detalle['nombre'],
                    ];
                    array_push($dataDetail, $d);
                }

                Detail::insert($dataDetail);

                $sale->siatEnviado=true;
                $sale->codigoRecepcion=$result->RespuestaServicioFacturacion->codigoRecepcion;
                $sale->cuf=$cuf;
                $sale->save();
               // $tickets=Ticket::where('sale_id',$sale->id)->get();
                return response()->json([
                    'sale' => Sale::where('id',$sale->id)->with('client')->with('details')->with('user')->first(),
                   // "tickets"=>$tickets,
                    "error"=>"",
                ]);
            }else{
                return response()->json(['message' => $result->RespuestaServicioFacturacion->mensajesList->descripcion], 400);
            }
        }catch (\Exception $e){
            //if(sizeof($request->detalleVenta) > 0){
            $sale=new Sale();
            $sale->numeroFactura=$numeroFactura;
            $sale->cuf="";
            $sale->cufd=$cufd->codigo;
            $sale->cui=$cui->codigo;
            $sale->codigoSucursal=$codigoSucursal;
            $sale->codigoPuntoVenta=$codigoPuntoVenta;
            $sale->fechaEmision=now();
            $sale->montoTotal=$request->montoTotal;
            $sale->usuario=$user->name;
            $sale->codigoDocumentoSector=$codigoDocumentoSector;
            $sale->user_id=$user->id;
            $sale->cufd_id=$cufd->id;
            $sale->client_id=$client->id;
            $sale->tipo="CANDY";
            $sale->leyenda=$leyenda;
            $sale->vip=$request->vip;
            $sale->credito=$request->tarjeta;
            $this->applyQrData($sale, $request);
            $sale->save();

            if ($request->client['email']!='' && $request->client['email']!= null  ){
                $details=[
                    "title"=>"Factura",
                    "body"=>"Gracias por su compra",
                    "online"=>false,
                    "anulado"=>false,
                    "habilitar" => false,
                    "cuf"=>"",
                    "numeroFactura"=>"",
                    "sale_id"=>$sale->id,
                    "carpeta"=>"archivos",
                ];
                Mail::to($request->client['email'])->send(new TestMail($details));
            }
            $dataDetail=[];
            foreach ($request->detalleVenta as $detalle){
                $d=[
                    'actividadEconomica'=>"590000",
                    'codigoProductoSin'=>"99100",
                    'cantidad'=>$detalle['cantidad'],
                    'precioUnitario'=>$detalle['precio'],
                    'subTotal'=>$detalle['subtotal'],
                    'sale_id'=>$sale->id,
//                    'programa_id'=>$detalle['programa_id'],
                    'product_id'=>$detalle['product_id'],
                    'descripcion'=>$detalle['nombre'],
                ];
                array_push($dataDetail, $d);
            }

            Detail::insert($dataDetail);

            $sale->siatEnviado=false;
            $sale->codigoRecepcion="";
            $sale->cuf=$cuf;
            $sale->save();
           // $tickets=Ticket::where('sale_id',$sale->id)->get();
            return response()->json([
                'sale' => Sale::where('id',$sale->id)->with('client')->with('details')->first(),
              // "tickets"=>$tickets,
                "error"=>"Se creo la venta pero no se pudo enviar a siat!!!",
            ]);
            return response()->json(['message' => $e->getMessage()], 500);
            }
        }
     // }
    }
    public function genXMLRental($id){

        $sale=Rental::find($id);
        $client=Client::find($sale->client_id);

        $codigoAmbiente=env('AMBIENTE');
        $codigoDocumentoSector=2; // 1 compraventa 2 alquiler 23 prevaloradas
        $codigoEmision=2; // 1 online 2 offline 3 masivo
        $codigoModalidad=env('MODALIDAD'); //1 electronica 2 computarizada
        $codigoPuntoVenta=0;
        $codigoSistema=env('CODIGO_SISTEMA');
        $tipoFacturaDocumento=1; // 1 con credito fiscal 2 sin creditofical 3 nota debito credito
        $unidadMedida=68;
        $medida='MESES';
        $codigoSucursal=0;
        $fechaEnvio=date("Y-m-d\TH:i:s.000",strtotime($sale->fechaEmision));

//        $user=User::find($request->user()->id);

        if (Cui::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->count()==0){
            return response()->json(['message' => 'No existe CUI para la venta!!'], 500);
        }
        if (Cufd::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->count()==0){
            return response()->json(['message' => 'No exite CUFD para la venta!!'], 500);
        }
        $cui=Cui::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->first();
        $cufd=Cufd::where('id',$sale->cufd_id)->first();


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
        $fechaCUF=date("YmdHis000",strtotime($sale->fechaEmision));
        $cuf = $cuf->obtenerCUF(env('NIT'), $fechaCUF, $codigoSucursal, $codigoModalidad, $codigoEmision, $tipoFacturaDocumento, $codigoDocumentoSector, $sale->numeroFactura, $codigoPuntoVenta);

        $cuf=$cuf.$cufd->codigoControl;

        $sale->cuf=$cuf;
        $sale->save();

        $text="<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<facturaElectronicaAlquilerBienInmueble xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'
                                        xsi:noNamespaceSchemaLocation='facturaElectronicaAlquilerBienInmueble.xsd'>
    <cabecera>
        <nitEmisor>".env('NIT')."</nitEmisor>
        <razonSocialEmisor>".env('RAZON')."</razonSocialEmisor>
        <municipio>Oruro</municipio>
        <telefono>".env('TELEFONO')."</telefono>
        <numeroFactura>$sale->numeroFactura</numeroFactura>
        <cuf>$cuf</cuf>
        <cufd>".$cufd->codigo."</cufd>
        <codigoSucursal>$codigoSucursal</codigoSucursal>
        <direccion>".env('DIRECCION')."</direccion>
        <codigoPuntoVenta>$codigoPuntoVenta</codigoPuntoVenta>
        <fechaEmision>$fechaEnvio</fechaEmision>
        <nombreRazonSocial>".$client->nombreRazonSocial."</nombreRazonSocial>
        <codigoTipoDocumentoIdentidad>".$client->codigoTipoDocumentoIdentidad."</codigoTipoDocumentoIdentidad>
        <numeroDocumento>".$client->numeroDocumento."</numeroDocumento>
        <complemento>".$client->complemento."</complemento>
        <codigoCliente>".$client->id."</codigoCliente>
        <periodoFacturado>".$sale->periodoFacturado."</periodoFacturado>
        <codigoMetodoPago>1</codigoMetodoPago>
        <numeroTarjeta xsi:nil='true'/>
        <montoTotal>".$sale->montoTotal."</montoTotal>
        <montoTotalSujetoIva>".$sale->montoTotal."</montoTotalSujetoIva>
        <codigoMoneda>1</codigoMoneda>
        <tipoCambio>1</tipoCambio>
        <montoTotalMoneda>".$sale->montoTotal."</montoTotalMoneda>
        <descuentoAdicional xsi:nil='true'/>
        <codigoExcepcion>0</codigoExcepcion>
        <cafc xsi:nil='true'/>
        <leyenda>$sale->leyenda</leyenda>
        <usuario>".explode(" ", $sale->usuario)[0] ."</usuario>
        <codigoDocumentoSector>2</codigoDocumentoSector>
    </cabecera>
    <detalle>
        <actividadEconomica>681011</actividadEconomica>
        <codigoProductoSin>72111</codigoProductoSin>
        <codigoProducto>10101</codigoProducto>
        <descripcion>".$sale->descripcion."</descripcion>
        <cantidad>1</cantidad>
        <unidadMedida>$unidadMedida</unidadMedida>
        <precioUnitario>".$sale->montoTotal."</precioUnitario>
        <montoDescuento>0</montoDescuento>
        <subTotal>".$sale->montoTotal."</subTotal>
    </detalle>
</facturaElectronicaAlquilerBienInmueble>";
//TODO falta colocar el 0 o 1 en excepcion
        $xml = new SimpleXMLElement($text);
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        $nameFile=$sale->id;
        $dom->save("rentals/".$nameFile.'.xml');

        $firmar = new Firmar();
        $firmar->firmar("rentals/".$nameFile.'.xml');


        $xml = new DOMDocument();
        $xml->load("rentals/".$nameFile.'.xml');
        if (!$xml->schemaValidate('./facturaElectronicaAlquilerBienInmueble.xsd')) {
            return "invalid";
        }
        else {
//            return "validated";
        }
//        exit;


        $file = "rentals/".$nameFile.'.xml';
        $gzfile = "rentals/".$nameFile.'.xml'.'.gz';
        $fp = gzopen ($gzfile, 'w9');
        gzwrite ($fp, file_get_contents($file));
        gzclose($fp);
//        unlink($file);


        $archivo=$firmar->getFileGzip("rentals/".$nameFile.'.xml'.'.gz');
        $hashArchivo=hash('sha256', $archivo);
        // unlink($gzfile);
    }
    public function genXMLcandy($id)
    {

       $sale=Sale::find($id);
       $details=Detail::where('sale_id',$id)->get();
        $client=Client::find($sale->client_id);
        $fechacuf=date("Y-m-d",strtotime($sale->fechaEmision));

        $codigoAmbiente=env('AMBIENTE');
        $codigoDocumentoSector=1; // 1 compraventa 2 alquiler 23 prevaloradas
        $codigoEmision=2; // 1 online 2 offline 3 masivo
        $codigoModalidad=env('MODALIDAD'); //1 electronica 2 computarizada
        $codigoPuntoVenta=0;
        $codigoSistema=env('CODIGO_SISTEMA');
        $tipoFacturaDocumento=1; // 1 con credito fiscal 2 sin creditofical 3 nota debito credito

        $codigoSucursal=0;

//        $cui=Cui::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->first();
        $cufd=Cufd::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->whereDate('fechaVigencia',$fechacuf)->first();
        $cuf = new CUF();

        $fechaCUF=date("YmdHis000",strtotime($sale->fechaEmision));

        $cuf = $cuf->obtenerCUF(env('NIT'), $fechaCUF, $codigoSucursal, $codigoModalidad, $codigoEmision, $tipoFacturaDocumento, $codigoDocumentoSector, $sale->numeroFactura, $codigoPuntoVenta);
        $cuf = $cuf.$cufd->codigoControl;
        $sale->cuf=$cuf;
        $sale->siatEnviado=false;
        $sale->save();

        $detalleFactura="";
        foreach ($details as $detalle){
            $detalleFactura.="<detalle>
                <actividadEconomica>590000</actividadEconomica>
                <codigoProductoSin>99100</codigoProductoSin>
                <codigoProducto>".$detalle->product_id."</codigoProducto>
                <descripcion>".utf8_encode(str_replace("&","&amp;",$detalle->descripcion))."</descripcion>
                <cantidad>".$detalle->cantidad."</cantidad>
                <unidadMedida>62</unidadMedida>
                <precioUnitario>".($detalle->subTotal/$detalle->cantidad)."</precioUnitario>
                <montoDescuento>0</montoDescuento>
                <subTotal>".$detalle->subTotal."</subTotal>
                <numeroSerie xsi:nil='true'/>
                <numeroImei xsi:nil='true'/>
            </detalle>";
        }
        $fechaEnvio=date("Y-m-d\TH:i:s.000",strtotime($sale->fechaEmision));
        //$cuf = new CUF();
        //$cuf = $cuf->obtenerCUF(env('NIT'), date("YmdHis000",strtotime($sale->fechaEmision)), $codigoSucursal, $codigoModalidad, $codigoEmision, $tipoFacturaDocumento, $codigoDocumentoSector, $sale->numeroFactura, $codigoPuntoVenta);
        //$cuf=$cuf.$cufd->codigoControl;

        $text="<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
        <facturaElectronicaCompraVenta xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:noNamespaceSchemaLocation='facturaElectronicaCompraVenta.xsd'>
        <cabecera>
        <nitEmisor>".env('NIT')."</nitEmisor>
        <razonSocialEmisor>".env('RAZON')."</razonSocialEmisor>
        <municipio>Oruro</municipio>
        <telefono>".env('TELEFONO')."</telefono>
        <numeroFactura>$sale->numeroFactura</numeroFactura>
        <cuf>".$sale->cuf."</cuf>
        <cufd>".$sale->cufd."</cufd>
        <codigoSucursal>$sale->codigoSucursal</codigoSucursal>
        <direccion>".env('DIRECCION')."</direccion>
        <codigoPuntoVenta>$sale->codigoPuntoVenta</codigoPuntoVenta>
        <fechaEmision>$fechaEnvio</fechaEmision>
        <nombreRazonSocial>".utf8_encode(str_replace("&","&amp;",$client->nombreRazonSocial))."</nombreRazonSocial>
        <codigoTipoDocumentoIdentidad>".$client->codigoTipoDocumentoIdentidad."</codigoTipoDocumentoIdentidad>
        <numeroDocumento>".$client->numeroDocumento."</numeroDocumento>
        <complemento>".$client->complemento."</complemento>
        <codigoCliente>".$client->id."</codigoCliente>
        <codigoMetodoPago>1</codigoMetodoPago>
        <numeroTarjeta xsi:nil='true'/>
        <montoTotal>".$sale->montoTotal."</montoTotal>
        <montoTotalSujetoIva>".$sale->montoTotal."</montoTotalSujetoIva>
        <codigoMoneda>1</codigoMoneda>
        <tipoCambio>1</tipoCambio>
        <montoTotalMoneda>".$sale->montoTotal."</montoTotalMoneda>
        <montoGiftCard xsi:nil='true'/>
        <descuentoAdicional>0</descuentoAdicional>
        <codigoExcepcion>".($client->codigoTipoDocumentoIdentidad==5?1:0)."</codigoExcepcion>
        <cafc xsi:nil='true'/>
        <leyenda>$sale->leyenda</leyenda>
        <usuario>".explode(" ", $sale->usuario)[0] ."</usuario>
        <codigoDocumentoSector>".$sale->codigoDocumentoSector."</codigoDocumentoSector>
        </cabecera>";
        $text.=$detalleFactura;
        $text.="</facturaElectronicaCompraVenta>";

        $xml = new SimpleXMLElement($text);
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        $nameFile=$sale->id;
        $dom->save("archivos/".$nameFile.'.xml');

        $firmar = new Firmar();
        $firmar->firmar("archivos/".$nameFile.'.xml');


        $xml = new DOMDocument();
        $xml->load("archivos/".$nameFile.'.xml');
        /*if (!$xml->schemaValidate('./facturaElectronicaCompraVenta.xsd')) {
            echo "invalid";
        }
        else {
        }*/

        error_log("FIRMA: ");

        $file = "archivos/".$nameFile.'.xml';
        $gzfile = "archivos/".$nameFile.'.xml'.'.gz';
        $fp = gzopen ($gzfile, 'w9');
        gzwrite ($fp, file_get_contents($file));
        gzclose($fp);

        $archivo=$firmar->getFileGzip("archivos/".$nameFile.'.xml'.'.gz');
        $hashArchivo=hash('sha256', $archivo);
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SaleCandy  $saleCandy
     * @return \Illuminate\Http\Response
     */
    public function show(SaleCandy $saleCandy)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SaleCandy  $saleCandy
     * @return \Illuminate\Http\Response
     */
    public function edit(SaleCandy $saleCandy)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateSaleCandyRequest  $request
     * @param  \App\Models\SaleCandy  $saleCandy
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSaleCandyRequest $request, SaleCandy $saleCandy)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SaleCandy  $saleCandy
     * @return \Illuminate\Http\Response
     */
    public function destroy(SaleCandy $saleCandy)
    {
        //
    }

    private function insertarRecibo(Request $request, $client)
    {
        $numeroFactura=0;
        $codigoSucursal=0;
        $codigoPuntoVenta=0;
        $codigoDocumentoSector=0;
        $sale=new Sale();
        $sale->numeroFactura=$numeroFactura;
        $sale->cuf="";
        $sale->cufd="";
        $sale->cui="";
        $sale->codigoSucursal=$codigoSucursal;
        $sale->codigoPuntoVenta=$codigoPuntoVenta;
        $sale->fechaEmision=now();
        $sale->montoTotal=$request->montoTotal;
        $sale->usuario=$request->user()->name;
        $sale->codigoDocumentoSector=$codigoDocumentoSector;
        $sale->user_id=$request->user()->id;
        $sale->cufd_id=null;
        $sale->client_id=$client->id;
        $sale->tipo="CANDY";
        $sale->leyenda="";
        $sale->venta="R";
        $sale->vip=$request->vip;
        $sale->credito=$request->tarjeta;
        $this->applyQrData($sale, $request);
        $sale->save();


        $dataDetail=[];
        foreach ($request->detalleVenta as $detalle){
            $d=[
                'actividadEconomica'=>"590000",
                'codigoProductoSin'=>"99100",
                'cantidad'=>$detalle['cantidad'],
                'precioUnitario'=>$detalle['precio'],
                'subTotal'=>$detalle['subtotal'],
                'sale_id'=>$sale->id,
                'product_id'=>$detalle['product_id'],
                'descripcion'=>$detalle['nombre'],

//                'programa_id'=>$detalle['programa_id'],
//                'pelicula_id'=>$detalle['pelicula_id'],
//                'descripcion'=>$detalle['pelicula'],
            ];
            array_push($dataDetail, $d);
        }

        Detail::insert($dataDetail);

        $sale->siatEnviado=true;
        $sale->codigoRecepcion="";
        $sale->cuf="";
        $sale->save();

        $sale=Sale::where('id',$sale->id)->with('client')->with('details')->first();
        $sale->siatEnviado=false;
        return response()->json([
            'sale' => $sale,
            "error"=>"Se creo la venta!!!",
        ]);
//        return response()->json(['message' => $e->getMessage()], 500);
    }
    private function insertarVip(Request $request, Client $client)
    {
        $numeroFactura=0;
        $codigoSucursal=0;
        $codigoPuntoVenta=0;
        $codigoDocumentoSector=0;
        $user=User::find($request->user()->id);
            $sale=new Sale();
            $sale->numeroFactura=$numeroFactura;
            $sale->cuf="";
            $sale->cufd="";
            $sale->cui="";
            $sale->codigoSucursal=$codigoSucursal;
            $sale->codigoPuntoVenta=$codigoPuntoVenta;
            $sale->fechaEmision=now();
            $sale->montoTotal=$request->montoTotal;
            $sale->usuario=$user->name;
            $sale->codigoDocumentoSector=$codigoDocumentoSector;
            $sale->user_id=$user->id;
            $sale->cufd_id=null;
            $sale->client_id=$client->id;
            $sale->tipo="CANDY";
            $sale->leyenda="";
            $sale->vip=$request->vip;
            $sale->credito=$request->tarjeta;
            $sale->venta="R";
            $this->applyQrData($sale, $request);
            $sale->save();

            $dataDetail=[];
            foreach ($request->detalleVenta as $detalle){
                $d=[
                    'actividadEconomica'=>"590000",
                    'codigoProductoSin'=>"99100",
                    'cantidad'=>$detalle['cantidad'],
                    'precioUnitario'=>$detalle['precio'],
                    'subTotal'=>$detalle['subtotal'],
                    'sale_id'=>$sale->id,
//                        'programa_id'=>$detalle['programa_id'],
                    'product_id'=>$detalle['product_id'],
                    'descripcion'=>$detalle['nombre'],
                ];
                array_push($dataDetail, $d);
            }

            Detail::insert($dataDetail);

            $sale->siatEnviado=true;
            $sale->codigoRecepcion="";
            $sale->cuf="";
            $sale->save();
            // $tickets=Ticket::where('sale_id',$sale->id)->get();
        $sale=Sale::where('id',$sale->id)->with('client')->with('details')->with('user')->first();
        $sale->siatEnviado=false;
        $codigo=$this->hexToStr($request->codigoTarjeta);

        $result=DB::connection('tarjeta')->select("SELECT * from cliente  WHERE codigo='$codigo'")[0];

        DB::connection('tarjeta')->select("
            UPDATE cliente SET saldo=saldo-$sale->montoTotal WHERE codigo='$codigo'
        ");
        $fecha=date('Y-m-d');
        $monto=$sale->montoTotal;
        $numero=$sale->id;
        $cliente=$result->id;
        //$cliente=$client->id;
        DB::connection('tarjeta')->select("
        INSERT INTO historial (fecha, lugar, monto, numero, cliente_id) VALUES ('$fecha', 'CANDY BAR', $monto, $numero, $cliente)
        ");
            return response()->json([
                'sale' => $sale,
                // "tickets"=>$tickets,
                "error"=>"",
            ]);
    }
    public function hexToStr($hex){
        $string='';
        for ($i=0; $i < strlen($hex)-1; $i+=2){
            $string .= chr(hexdec($hex[$i].$hex[$i+1]));
        }
        return $string;
    }
}
