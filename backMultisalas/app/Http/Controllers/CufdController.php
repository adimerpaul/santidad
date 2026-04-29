<?php

namespace App\Http\Controllers;

use App\Models\Cufd;
use App\Http\Requests\StoreCufdRequest;
use App\Http\Requests\UpdateCufdRequest;
use App\Models\Cui;

class CufdController extends Controller
{
    public function index(){
        $perPage = request()->get('per_page', 10); // Puedes cambiar el valor por defecto
        return Cufd::with(['sales' => function($query) {
            $query->where('siatEnviado', false);
        }])
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function listCufd()
    {
       //listar de los ultimos 60 dias
       return Cufd::where('fechaCreacion', '>=', now()->subDays(60))
            ->orderBy('id', 'desc')
            ->get(); 

    }

    public function create()
    {
        //
    }

    public function store(StoreCufdRequest $request)
    {
        if (Cufd::where('codigoPuntoVenta', $request->codigoPuntoVenta)->where('codigoSucursal', $request->codigoSucursal)->where('fechaVigencia','>=', now())->count()>=1){
            return response()->json(['message' => 'El CUFD ya existe'], 400);
        }else{
            $cui=Cui::where('codigoPuntoVenta', $request->codigoPuntoVenta)->where('codigoSucursal', $request->codigoSucursal)->where('fechaVigencia','>=', now());
            if ($cui->count()==0){
                return response()->json(['message' => 'El CUI no existe'], 400);
            }
            $client = new \SoapClient(env("URL_SIAT")."FacturacionCodigos?WSDL",  [
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
            $result= $client->cufd([
                "SolicitudCufd"=>[
                    "codigoAmbiente"=>env('AMBIENTE'),
                    "codigoModalidad"=>env('MODALIDAD'),
                    "codigoPuntoVenta"=>$request->codigoPuntoVenta,
                    "codigoSistema"=>env('CODIGO_SISTEMA'),
                    "codigoSucursal"=>$request->codigoSucursal,
                    "cuis"=> $cui->first()->codigo,
                    "nit"=>env('NIT'),
                ]
            ]);
            error_log("result: ".json_encode($result));

            $cufd = new Cufd();
            $cufd->codigo = $result->RespuestaCufd->codigo;
            $cufd->codigoControl = $result->RespuestaCufd->codigoControl;
//            $cufd->fechaVigencia =  date('Y-m-d H:i:s', strtotime($result->RespuestaCufd->fechaVigencia));
            $cufd->fechaVigencia =  date('Y-m-d H:i:s', strtotime (date('Y-m-d 23:59:59')));
            $cufd->fechaCreacion =  date('Y-m-d H:i:s');
            $cufd->codigoPuntoVenta = $request->codigoPuntoVenta;
            $cufd->codigoSucursal = $request->codigoSucursal;
            $cufd->save();
            return response()->json(['success' => 'CUFD creado correctamente'], 200);
        }
    }

    public function lllll(StoreCufdRequest $request)
    {
            $cui=Cui::where('codigoPuntoVenta', $request->codigoPuntoVenta)->where('codigoSucursal', $request->codigoSucursal)->where('fechaVigencia','>=', now());
            if ($cui->count()==0){
                return response()->json(['message' => 'El CUI no existe'], 400);
            }
            for($i=0;$i<100;$i++) {
                $client = new \SoapClient(env("url_siat")."FacturacionCodigos?WSDL", [
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
                $result = $client->cufd([
                    "SolicitudCufd" => [
                        "codigoAmbiente" => env('AMBIENTE'),
                        "codigoModalidad" => env('MODALIDAD'),
                        "codigoPuntoVenta" => $request->codigoPuntoVenta,
                        "codigoSistema" => env('CODIGO_SISTEMA'),
                        "codigoSucursal" => $request->codigoSucursal,
                        "cuis" => $cui->first()->codigo,
                        "nit" => env('NIT'),
                    ]
                ]);
            }
            return response()->json(['success' => 'CUFD creado correctamente'], 200);
    }

    public function show(Cufd $cufd)
    {
        //
    }

    public function edit(Cufd $cufd)
    {
        //
    }

    public function update(UpdateCufdRequest $request, Cufd $cufd)
    {
        //
    }

    public function destroy(Cufd $cufd)
    {
        //
    }
}
