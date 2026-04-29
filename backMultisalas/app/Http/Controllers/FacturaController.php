<?php

namespace App\Http\Controllers;

use App\Models\Cufd;
use App\Models\Cui;
use App\Models\Factura;
use App\Http\Requests\StoreFacturaRequest;
use App\Http\Requests\UpdateFacturaRequest;
use App\Imports\UsersImport;
use App\Models\Sale;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
class FacturaController extends Controller{
    public function buscarFacturas(){
        $facturas=Factura::where('impuesto', '=', 'NO')->get();
        return $facturas;
    }
    public function index(){ return Factura::all(); }
    public function show(Factura $factura){ return $factura; }
    public function store(StoreFacturaRequest $request){ return Factura::create($request->all()); }
    public function update(UpdateFacturaRequest $request, Factura $factura){ return $factura->update($request->all()); }
    public function destroy(Factura $factura){ return $factura->delete(); }

    public function getYearMonthFacturas(Request $request){
        $year=$request->anio;
        $month=$request->mes;
        $facturas=Factura::whereYear('fecha', '=', $year)
            ->whereMonth('fecha', '=', $month)
            ->where('impuesto', '=', 'NO')
            ->get();
//        $sumFacturas=Factura::whereYear('fecha', '=', $year)
//            ->whereMonth('fecha', '=', $month)
//            ->where('estado', '=', 'VALIDA')
//            ->sum('subtotal');
//        $sumSales=Sale::whereYear('fecha', '=', $year)
//            ->whereMonth('fecha', '=', $month)
//            ->sum('montoTotal');
//        $countFacturas=Factura::whereYear('fecha', '=', $year)
//            ->whereMonth('fecha', '=', $month)
//            ->where('impuesto', '=', 'SI')
//            ->count();
//        $countSales=Sale::whereYear('fecha', '=', $year)
//            ->whereMonth('fecha', '=', $month)
//            ->count();
        return $facturas;
    }

    public function uploadFileZip(Request $request)
    {
        $file = $request->file('archivo');
        $fileName = 'archivoVentas' . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('VentasXlsx'), $fileName);
        return $fileName;
    }
    public function unzipFile($fileName)
    {
        $zip = new \ZipArchive();
        $zip->open('VentasXlsx/'.$fileName); // open the zip file to extract
        $zip->extractTo('VentasXlsx');
        $zip->close();
    }

    public function import(Request $request)
    {
        $fileName=$this->uploadFileZip($request);
        error_log($fileName);
        $this->unzipFile($fileName);
        $array = Excel::toArray(new UsersImport, 'VentasXlsx/archivoVentas.xlsx');
        $insert=[];
        Factura::truncate();
        foreach ($array[0] as $row) {
//            $factura= Factura::where('nFactura', $row['no_de_la_factura'])->where('cuf', $row['codigo_de_autorizacion'])->first();
            error_log($row['no']);
//            if($factura) {
//                $factura->n = $row['no'];
//                $factura->fecha = $this->convertDate($row['fecha_de_la_factura']);
//                $factura->nFactura = $row['no_de_la_factura'];
//                $factura->cuf=$row['codigo_de_autorizacion'];
//                $factura->nit=$row['nit_ci_cliente'];
//                $factura->complemento=$row['complemento'];
//                $factura->nombre=$row['nombre_o_razon_social'];
//                $factura->importe=$row['importe_total_de_la_venta'];
//                $factura->ice=$row['importe_ice'];
//                $factura->iehd=$row['importe_iehd'];
//                $factura->ipj=$row['importe_ipj'];
//                $factura->tasas=$row['tasas'];
//                $factura->noSujeto=$row['otros_no_sujetos_al_iva'];
//                $factura->exentas=$row['exportaciones_y_operaciones_exentas'];
//                $factura->tasaCero=$row['ventas_gravadas_a_tasa_cero'];
//                $factura->subTotal=$row['subtotal'];
//                $factura->rebajas=$row['descuentos_bonificaciones_y_rebajas_sujetas_al_iva'];
//                $factura->card=$row['importe_gift_card'];
//                $factura->importeBase=$row['importe_base_para_debito_fiscal'];
//                $factura->iva=$row['debito_fiscal'];
//                $factura->estado=$row['estado'];
//                $factura->codigoControl=$row['codigo_de_control'];
//                $factura->tipoVenta=$row['tipo_de_venta'];
//                $factura->derecho=$row['con_derecho_a_credito_fiscal'];
//                $factura->consolidado=$row['estado_consolidacion'];
//                $factura->save();
//            }else{
                $f = new Factura();
                $f->n = $row['no'];
                $f->fecha = $this->convertDate($row['fecha_de_la_factura']);
                $f->nFactura = $row['no_de_la_factura'];
                $f->cuf=$row['codigo_de_autorizacion'];
                $f->nit=$row['nit_ci_cliente'];
                $f->complemento=$row['complemento'];
                $f->nombre=$row['nombre_o_razon_social'];
                $f->importe=$row['importe_total_de_la_venta'];
                $f->ice=$row['importe_ice'];
                $f->iehd=$row['importe_iehd'];
                $f->ipj=$row['importe_ipj'];
                $f->tasas=$row['tasas'];
                $f->noSujeto=$row['otros_no_sujetos_al_iva'];
                $f->exentas=$row['exportaciones_y_operaciones_exentas'];
                $f->tasaCero=$row['ventas_gravadas_a_tasa_cero'];
                $f->subTotal=$row['subtotal'];
                $f->rebajas=$row['descuentos_bonificaciones_y_rebajas_sujetas_al_iva'];
                $f->card=$row['importe_gift_card'];
                $f->importeBase=$row['importe_base_para_debito_fiscal'];
                $f->iva=$row['debito_fiscal'];
                $f->estado=$row['estado'];
                $f->codigoControl=$row['codigo_de_control'];
                $f->tipoVenta=$row['tipo_de_venta'];
                $f->derecho=$row['con_derecho_a_credito_fiscal'];
                $f->consolidado=$row['estado_consolidacion'];
//                $exitCuf=Sale::where('cuf', trim($row['codigo_de_autorizacion']))->exists();
//                $f->impuesto=($exitCuf)?'SI':'NO';
                $f->impuesto='NO';
                $f->save();
//           $insert[]=[
//                "n"=> $row['no'],
//                "fecha"=> $this->convertDate($row['fecha_de_la_factura']),
//                "nFactura "=> $row['no_de_la_factura'],
//                "cuf"=>$row['codigo_de_autorizacion'],
//                "nit"=>$row['nit_ci_cliente'],
//                "complemento"=>$row['complemento'],
//                "nombre"=>$row['nombre_o_razon_social'],
//                "importe"=>$row['importe_total_de_la_venta'],
//                "ice"=>$row['importe_ice'],
//                "iehd"=>$row['importe_iehd'],
//                "ipj"=>$row['importe_ipj'],
//                "tasas"=>$row['tasas'],
//                "noSujeto"=>$row['otros_no_sujetos_al_iva'],
//                "exentas"=>$row['exportaciones_y_operaciones_exentas'],
//                "tasaCero"=>$row['ventas_gravadas_a_tasa_cero'],
//                "subTotal"=>$row['subtotal'],
//                "rebajas"=>$row['descuentos_bonificaciones_y_rebajas_sujetas_al_iva'],
//                "card"=>$row['importe_gift_card'],
//                "importeBase"=>$row['importe_base_para_debito_fiscal'],
//                "iva"=>$row['debito_fiscal'],
//                "estado"=>$row['estado'],
//                "codigoControl"=>$row['codigo_de_control'],
//                "tipoVenta"=>$row['tipo_de_venta'],
//                "derecho"=>$row['con_derecho_a_credito_fiscal'],
//                "consolidado"=>$row['estado_consolidacion'],
//                "impuesto"=>'NO',
//                ];
//            }
//            echo $this->convertDate($row['fecha_de_la_factura']).'<br>';
        }
//        $division=1000;
//        $facturas = array_chunk($insert, $division);
////        error_log('facturas: '.json_encode($facturas));
////        return $facturas;
//        $contador=0;
//        foreach ($facturas as $factura){
//            $contador++;
//            error_log('contador: '.$contador);
//            Factura::insert($factura);
//        }
//        Factura::insert($insert);
//        Factura::create($facturas);
    }
    public function convertDate($date){
        $date = str_replace('/', '-', $date);
        return date('Y-m-d', strtotime($date));
    }
    public function anularMasivo(){
        $facturas=Factura::where('impuesto', '=', 'NO')->get();

//        return $request->all();
        $codigoAmbiente=env('AMBIENTE');
        $codigoDocumentoSector=1; // 1 compraventa 2 alquiler 23 prevaloradas
        $codigoEmision=1; // 1 online 2 offline 3 masivo
        $codigoModalidad=env('MODALIDAD'); //1 electronica 2 computarizada
        $codigoPuntoVenta=0;
        $codigoSistema=env('CODIGO_SISTEMA');
        $tipoFacturaDocumento=1; // 1 con credito fiscal 2 sin creditofical 3 nota debito credito
        $codigoSucursal=0;
        $nit=ENV('NIT');

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
            foreach ($facturas  as $factura){
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
                        "cufd"=>$cufd->codigo,
                        "cuis"=>$cui->codigo,
                        "nit"=>env('NIT'),
                        "tipoFacturaDocumento"=>$tipoFacturaDocumento,
                        "codigoMotivo"=>1,
                        "cuf"=>$factura->cuf,
                    ]
                ]);
                error_log("result".json_encode($result));
            }

        }catch (\Exception $e) {
            //return response()->json(['error' => $e->getMessage()]);
            return response()->json(['message' => 'anulado error'], 400);
        }
    }

}
