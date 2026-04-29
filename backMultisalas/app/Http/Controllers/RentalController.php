<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Mail\TestMail;
use App\Models\Client;
use App\Models\Cufd;
use App\Models\Cui;
use App\Models\Detail;
use App\Models\Leyenda;
use App\Models\Momentaneo;
use App\Models\Programa;
use App\Models\Rental;
use App\Models\User;
use App\Http\Requests\StoreRentalRequest;
use App\Http\Requests\UpdateRentalRequest;
use App\Models\Sale;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use SimpleXMLElement;
use DOMDocument;

class RentalController extends Controller
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
    public function rentalConsulta(Request $request)
    {
        $rental = Rental::whereDate('fechaEmision','>=', $request->fechaInicio)
            ->whereDate('fechaEmision','<=', $request->fechaFin)
            ->with('client')->with('user')
            ->orderBy('id', 'desc')
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
     * @param  \App\Http\Requests\StoreRentalRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSaleRequest $request)
    {
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

        $codigoAmbiente=env('AMBIENTE');
        $codigoDocumentoSector=2; // 1 compraventa 2 alquiler 23 prevaloradas
        $codigoEmision=1; // 1 online 2 offline 3 masivo
        $codigoModalidad=env('MODALIDAD'); //1 electronica 2 computarizada
        $codigoPuntoVenta=0;
        $codigoSistema=env('CODIGO_SISTEMA');
        $tipoFacturaDocumento=1; // 1 con credito fiscal 2 sin creditofical 3 nota debito credito

        $codigoSucursal=0;

        $user=User::find($request->user()->id);

        if (Cui::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->count()==0){
            return response()->json(['message' => 'No existe CUI para la venta!!'], 500);
        }
        if (Cufd::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->count()==0){
            return response()->json(['message' => 'No exite CUFD para la venta!!'], 500);
        }
        $cui=Cui::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->first();
        $cufd=Cufd::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->first();
//        if (Sale::where('cui', $cui->codigo)->count()==0){
//            $numeroFactura=1;
//        }else{
//            $sale=Sale::where('cui',$cui->codigo)->orderBy('numeroFactura', 'desc')->first();
//            $numeroFactura=intval($sale->numeroFactura)+1;
//        }

        if (Rental::where('cufd', $cufd->codigo)->count()==0){
            $numeroFactura=1;
        }else{
            $sale=Rental::where('cufd',$cufd->codigo)->orderBy('numeroFactura', 'desc')->first();
            $numeroFactura=intval($sale->numeroFactura)+1;
        }
        $leyenda=Leyenda::where("codigoActividad","681011")->get();
        $count=$leyenda->count();
        $leyenda=$leyenda[rand(0,$count-1)]->descripcionLeyenda;
        $sale=new Rental();
//        $sale->numeroFactura=$numeroFactura;
//        $sale->cuf="";
//        $sale->cufd=$cufd->codigo;
//        $sale->cui=$cui->codigo;
//        $sale->codigoSucursal=$codigoSucursal;
//        $sale->codigoPuntoVenta=$codigoPuntoVenta;
//        $sale->fechaEmision=now();
//        $sale->montoTotal=$request->montoTotal;
//        $sale->usuario=$user->name;
//        $sale->codigoDocumentoSector=$codigoDocumentoSector;
//        $sale->user_id=$user->id;
//        $sale->cufd_id=$cufd->id;
//        $sale->client_id=$request->client->id;
        $unidadMedida=68;
        $medida='MESES';
        $sale->numeroFactura=$numeroFactura;
        $sale->cuf="";
        $sale->cufd=$cufd->codigo;
        $sale->cui=$cui->codigo;
        $sale->codigoSucursal=$codigoSucursal;
        $sale->codigoPuntoVenta=$codigoPuntoVenta;
        $sale->fechaEmision=now();
        $sale->montoTotal=$request->montoTotal;
        $sale->usuario=$user->name;
        $sale->periodoFacturado=$request->periodoFacturado;
        $sale->codigoDocumentoSector=$codigoDocumentoSector;
        $sale->actividadEconomica=681011;
        $sale->codigoProductoSin=72111;
        $sale->codigoProducto="10101";
        $sale->descripcion=$request->descripcion;
        $sale->cantidad=1;
        $sale->medida=$medida;
        $sale->unidadMedida=$unidadMedida;
        $sale->leyenda=$leyenda;
        $sale->precioUnitario=$request->montoTotal;
        $sale->montoDescuento=0;
        $sale->subTotal=$request->montoTotal;
        $sale->user_id=$user->id;
        $sale->cufd_id=$cufd->id;
        $sale->client_id=$client->id;
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
        $fechaEnvio=date("Y-m-d\TH:i:s.000");
        $cuf = $cuf->obtenerCUF(env('NIT'), date("YmdHis000"), $codigoSucursal, $codigoModalidad, $codigoEmision, $tipoFacturaDocumento, $codigoDocumentoSector, $numeroFactura, $codigoPuntoVenta);
        $cuf=$cuf.$cufd->codigoControl;
        $text="<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<facturaElectronicaAlquilerBienInmueble xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'
                                        xsi:noNamespaceSchemaLocation='facturaElectronicaAlquilerBienInmueble.xsd'>
    <cabecera>
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
        <nombreRazonSocial>".$client->nombreRazonSocial."</nombreRazonSocial>
        <codigoTipoDocumentoIdentidad>".$client->codigoTipoDocumentoIdentidad."</codigoTipoDocumentoIdentidad>
        <numeroDocumento>".$client->numeroDocumento."</numeroDocumento>
        <complemento>".$client->complemento."</complemento>
        <codigoCliente>".$client->id."</codigoCliente>
        <periodoFacturado>".$request->periodoFacturado."</periodoFacturado>
        <codigoMetodoPago>1</codigoMetodoPago>
        <numeroTarjeta xsi:nil='true'/>
        <montoTotal>".$request->montoTotal."</montoTotal>
        <montoTotalSujetoIva>".$request->montoTotal."</montoTotalSujetoIva>
        <codigoMoneda>1</codigoMoneda>
        <tipoCambio>1</tipoCambio>
        <montoTotalMoneda>".$request->montoTotal."</montoTotalMoneda>
        <descuentoAdicional xsi:nil='true'/>
        <codigoExcepcion>0</codigoExcepcion>
        <cafc xsi:nil='true'/>
        <leyenda>$leyenda</leyenda>
        <usuario>".$user->name."</usuario>
        <codigoDocumentoSector>2</codigoDocumentoSector>
    </cabecera>
    <detalle>
        <actividadEconomica>681011</actividadEconomica>
        <codigoProductoSin>72111</codigoProductoSin>
        <codigoProducto>10101</codigoProducto>
        <descripcion>".$request->descripcion."</descripcion>
        <cantidad>1</cantidad>
        <unidadMedida>$unidadMedida</unidadMedida>
        <precioUnitario>".$request->montoTotal."</precioUnitario>
        <montoDescuento>0</montoDescuento>
        <subTotal>".$request->montoTotal."</subTotal>
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
        unlink($gzfile);
//        return $request;
//        exit;




        try {
            $client = new \SoapClient(env("URL_SIAT")."ServicioFacturacionElectronica?WSDL",  [
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
            $result= $client->recepcionFactura([
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
//            return var_dump($result);
//            exit;
            $sale->siatEnviado=true;
            $sale->codigoRecepcion=$result->RespuestaServicioFacturacion->codigoRecepcion;
        }catch (\Exception $e) {
//            return response()->json(['error' => $e->getMessage()]);
            $sale->siatEnviado=false;
        }

        $sale->cuf=$cuf;

//
        $sale->save();
        if ($request->client['email']!='' && $request->client['email']!=null ){
            $details=[
                "title"=>"Factura",
                "body"=>"Gracias por su compra",
                "online"=>$sale->siatEnviado,
                "sale_id"=>$sale->id,
                "anulado"=>false,
                "habilitar" => false,
                "cuf"=>"",
                "numeroFactura"=>"",
                "carpeta"=>"rentals",
            ];
            Mail::to($request->client['email'])->send(new TestMail($details));
        }
        return response()->json(['rental' => Rental::where('id',$sale->id)->with('client')->first()]);

    }
    public function anularRental(Request $request){
        //return $request->sale['id'];
        $codigoAmbiente=env('AMBIENTE');
        $codigoDocumentoSector=1; // 1 compraventa 2 alquiler 23 prevaloradas
        $codigoEmision=1; // 1 online 2 offline 3 masivo
        $codigoModalidad=env('MODALIDAD'); //1 electronica 2 computarizada
        $codigoPuntoVenta=$request->rental['codigoPuntoVenta'];
        $codigoSistema=env('CODIGO_SISTEMA');
        $tipoFacturaDocumento=1; // 1 con credito fiscal 2 sin creditofical 3 nota debito credito
        $codigoSucursal=$request->rental['codigoSucursal'];
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
                    "cufd"=>$request->rental['cufd'],
                    "cuis"=>$request->rental['cui'],
                    "nit"=>env('NIT'),
                    "tipoFacturaDocumento"=>$tipoFacturaDocumento,
                    "codigoMotivo"=>$request->motivo['codigoClasificador'],
                    "cuf"=>$request->rental['cuf']
                ]
            ]);
            error_log(json_encode($result));
            if($result->RespuestaServicioFacturacion->transaccion){
//            if(true){
                error_log($request->rental['id']);
                $rental=Rental::find($request->rental['id']);
                $rental->siatAnulado=1;
                $rental->user_anular=$user->name;
                $rental->save();
                error_log("anulado rental");
                $client=Client::find($rental->client_id);
                if ($client->email!=''){
                    $details=[
                        "title"=>"Factura",
                        "body"=>"Factura anulada",
                        "online"=>true,
                        "anulado"=>true,
                        "habilitar" => false,
                        "cuf"=>$rental->cuf,
                        "numeroFactura"=>$rental->numeroFactura,
                        "sale_id"=>$rental->id,
                        "carpeta"=>"rentals",
                    ];
                    Mail::to($client->email)->send(new TestMail($details));
                }
            }else{
                return response()->json(['message' => $result], 400);
            }
        }catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }
        public function revertirAnularRental(Request $request){
        $token= env('TOKEN');

        $sale=Sale::find($request->id);
        $codigoAmbiente=env('AMBIENTE');
        $codigoDocumentoSector=1; // 1 compraventa 2 alquiler 23 prevaloradas
        $codigoEmision=1; // 1 online 2 offline 3 masivo
        $codigoModalidad=env('MODALIDAD'); //1 electronica 2 computarizada
        $codigoPuntoVenta=0;
        $codigoSistema=env('CODIGO_SISTEMA');
        $tipoFacturaDocumento=1; // 1 con credito fiscal 2 sin creditofical 3 nota debito credito
        $codigoSucursal=0;
        $nit=env('NIT');

        $user=User::find($request->user()->id);

        if (Cui::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->count()==0){
            return response()->json(['message' => 'No existe CUI para la venta!!'], 400);
        }
        if (Cufd::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->count()==0){
            return response()->json(['message' => 'No exite CUFD para la venta!!'], 400);
        }
        $cui=Cui::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->first();
        $cufd=Cufd::where('codigoPuntoVenta', $codigoPuntoVenta)->where('codigoSucursal', $codigoSucursal)->where('fechaVigencia','>=', now())->orderBy('id','desc')->first();


            $client = new \SoapClient(env("URL_SIAT")."ServicioFacturacionCompraVenta?WSDL",  [
                'stream_context' => stream_context_create([
                    'http' => [
                        'header' => "apikey: TokenApi " . $token,
                    ]
                ]),
                'cache_wsdl' => WSDL_CACHE_NONE,
                'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
                'trace' => 1,
                'use' => SOAP_LITERAL,
                'style' => SOAP_DOCUMENT,
            ]);

            $result= $client->reversionAnulacionFactura([
                "SolicitudServicioReversionAnulacionFactura"=>[
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
                    "cuf"=>$request->cuf
                ]
            ]);

            error_log(json_encode($result));
//            if($result->RespuestaServicioFacturacion->transaccion){
            if(true){
                $rental=Rental::with('client')->where('id',$sale->id)->first();
                error_log(json_encode($rental));
                $rental->siatAnulado=0;
                error_log("usuario habilitar: ".$user->name);
                //$rental->user_anular=$user->name;
                $rental->save();
                error_log("habilitado rental");
                try {
                    error_log("email cliente: ".$rental->client['email']);
                    if ($rental->client['email']!='' && $rental->client['email']!=null ){
                        error_log("email cliente: ".$rental->client['email']);
                        $details=[
                            "title"=>"Habilitar Factura",
                            "body"=>"La factura se ha habilitado Nuevamente, Gracias por su Preferencia",
                            "online"=>true,
                            "anulado"=>false,
                            "habilitar"=>true,
                            "cuf"=>$rental->cuf,
                            "numeroFactura"=>$rental->numeroFactura,
                            "sale_id"=>$rental->id,
                            "carpeta"=>"rentals",
                        ];
                        error_log("enviando mail");
                        Mail::to($rental->client['email'])->send(new TestMail($details));
                        error_log("mail enviado");
                    }
                }catch (\Exception $e){
                    error_log("error mail rental: ".$e->getMessage());
                }
                return response()->json([
                    'rental' => Rental::where('id',$rental->id)->with('client')->with('user')->first(),
                   // "tickets"=>$tickets,
                    "error"=>"",
                ]);
            }
            return $result;
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Rental  $rental
     * @return \Illuminate\Http\Response
     */
    public function show(Rental $rental)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Rental  $rental
     * @return \Illuminate\Http\Response
     */
    public function edit(Rental $rental)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateRentalRequest  $request
     * @param  \App\Models\Rental  $rental
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRentalRequest $request, Rental $rental)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Rental  $rental
     * @return \Illuminate\Http\Response
     */
    public function destroy(Rental $rental)
    {
        //
    }
}
