<?php

namespace App\Mail;

use App\Models\Medida;
use Dompdf\Dompdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Luecano\NumeroALetras\NumeroALetras;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class TestMail extends Mailable
{
    use Queueable, SerializesModels;
    public $details;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details=$details;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if ($this->details['anulado']){
            $datos['cuf']=$this->details['cuf'];
            $datos['numeroFactura']=$this->details['numeroFactura'];
            return $this->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'))
                ->view('anulado',$datos)
                ->subject('MULTICINES PLAZA')
                ->with($this->details);
            exit;
        }

        if ($this->details['habilitar']){
            $datos['cuf']=$this->details['cuf'];
            $datos['numeroFactura']=$this->details['numeroFactura'];
        return $this->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'))
            ->view('habilita',$datos)
//            ->attach($this->details['carpeta'].'/'.$this->details['sale_id'].'.pdf')
//            ->attach($this->details['carpeta'].'/'.$this->details['sale_id'].'.xml')
            ->subject('MULTICINES PLAZA')
            ->with($this->details);
            exit;
        }

        $pathXmlFile=$this->details['carpeta'].'/'.$this->details['sale_id'].'.xml';

        if (!file_exists($pathXmlFile)) return false;
        $nameFile = substr($pathXmlFile, 0, strlen($pathXmlFile) - 4);
        $content = file_get_contents($pathXmlFile);
        $xml = simplexml_load_string($content);
        $cuf = '';
        for ($i = 0; $i < strlen($xml->cabecera->cuf); $i++) {
            if (($i + 1) % 20 == 0) {
                $cuf .= substr($xml->cabecera->cuf, $i, 1) . "<br>";
            } else {
                $cuf .= substr($xml->cabecera->cuf, $i, 1);
            }
        }

        $html = self::generateHTML($xml,$cuf,$this->details['online']);
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter');
        $dompdf->render();
        file_put_contents($nameFile.'.pdf', $dompdf->output());

        return $this->from(env('MAIL_FROM_ADDRESS'),env('MAIL_FROM_NAME'))
            ->view('vista')
            ->attach($this->details['carpeta'].'/'.$this->details['sale_id'].'.pdf')
            ->attach($this->details['carpeta'].'/'.$this->details['sale_id'].'.xml')
            ->subject('MULTICINES PLAZA')
            ->with($this->details);
    }

    public function generateHTML($xml,$cuf,$online): string
    {
        if ($this->details['carpeta']=='rentals'){
            $rental='<tr>
                    <td class="right">
                        <div class="bold">Periodo Facturado:</div>
                    </td>
                    <td>
                        <div>' . $xml->cabecera->periodoFacturado . '</div>
                    </td>
                </tr>';
        }else{
            $rental='';
        }

        $formatter = new NumeroALetras();
        $literal = $formatter->toInvoice((float)$xml->cabecera->montoTotal, 2, 'Bolivianos');
        $detalles = "";
        $hora = substr($xml->cabecera->fechaEmision, 11, 8);
        $fecha = substr($xml->cabecera->fechaEmision, 0, 10);
        foreach ($xml->detalle as $d) {
//            if ($d->unidadMedida == Env::unidadMedida) {
//            } else {
            $med=Medida::where('codigoClasificador',$d->unidadMedida)->first();
            $d->unidadMedida = $med->descripcion;

//                $d->unidadMedida = Env::unidadMedidaOtroDescripcion;
//            }
            $detalles .= '
                <tr>
                    <td class="border">' . $d->codigoProducto . '</td>
                    <td class="border">' . $d->cantidad . '</td>
                    <td class="border">' . $d->unidadMedida . '</td>
                    <td class="border">' . $d->descripcion . '</td>
                    <td class="border right">' . $d->precioUnitario . '</td>
                    <td class="border right">0</td>
                    <td class="border right">' . $d->subTotal . '</td>
                </tr>
            ';
        }
        $url = env('URL_SIAT2') . "consulta/QR?nit=" . env('NIT') . "&cuf=" . $xml->cabecera->cuf . "&numero=" . $xml->cabecera->numeroFactura . "&t=2";
        $qrcode = base64_encode(QrCode::format('svg')->size(200)->errorCorrection('H')->generate($url));
        $doc = $xml->cabecera->complemento!=""? $xml->cabecera->numeroDocumento."-".$xml->cabecera->complemento: $xml->cabecera->numeroDocumento;
        $pieleyenda= $online?"Este documento es la Representación Gráfica de un Documento Fiscal Digital emitido en una modalidad de facturación en linea":"“Este documento es la
Representación Gráfica de un
Documento Fiscal Digital emitido
fuera de línea, verifique su envío
con su proveedor o en la página
web www.impuestos.gob.bo”.";
        return ('
        <!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
        *{
            font-size: 12px;
        }
        .bold{
            font-weight: bold;
        }
        .text-h1{
            font-size: 20px;
        }
        .text-h2{
            font-size: 10px;
        }
        .text-h5{
            font-size: 8px;
        }
        .text-h6{
            font-size: 7px;
        }
        .center{
            text-align: center;
        }
        .right{
            text-align: right;
        }
        .border{
            border: 1px solid black
        }
        .collapse{
            border-collapse: collapse;
        }
        .background{
            background: #edf2f7
        }
        .overflow-visible {
          white-space: initial;
        }
    </style>
</head>
<body>
<table width="100%"  class="collapse" >
    <tr>
        <td width="33%">
            <div class="bold center">' . $xml->cabecera->razonSocialEmisor . '</div>
            <div class="bold center">CASA MATRIZ</div>
            <div class="center">No. Punto de Venta ' . $xml->cabecera->codigoPuntoVenta . '</div>
        </td>
        <td></td>
        <td width="120px">
            <table>
                <tr>
                    <td valign=top width="130px">
                        <div class="bold">NIT</div>
                        <div class="bold">FACTURA N°</div>
                        <div class="bold">CÓD. AUTORIZACIÓN</div>
                    </td>
                    <td>
                        <div>' . $xml->cabecera->nitEmisor . '</div>
                        <div>' . $xml->cabecera->numeroFactura . '</div>
                        <div style="width: 120px">' . $cuf . '</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <div class="center">
                ' . $xml->cabecera->direccion . '
            </div>
            <div class="center">Teléfono: ' . $xml->cabecera->telefono . '</div>
            <div class="center">Oruro-Bolivia</div>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <div class="bold center text-h1">
                FACTURA
            </div>
            <div class="center">
                (Con Derecho a Crédito Fiscal)
            </div>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <table width="100%">
                <tr>
                    <td>
                        <div class="bold">Fecha: </div>
                    </td>
                    <td>
                        <div>' . $fecha . ' ' . $hora . '</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="bold">Nombre/Razón Social:</div>
                    </td>
                    <td>
                        <div>' . $xml->cabecera->nombreRazonSocial . '</div>
                    </td>
                </tr>
            </table>
        </td>
        <td>
            <table width="100%">
                <tr>
                    <td class="right">
                        <div class="bold">NIT/CI/CEX:</div>
                    </td>
                    <td>
                        <div>'. $doc .  '</div>
                    </td>
                </tr>
                <tr>
                    <td class="right">
                        <div class="bold">Cod. Cliente:</div>
                    </td>
                    <td>
                        <div>' . $xml->cabecera->codigoCliente . '</div>
                    </td>
                </tr>
                '.$rental.'
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <table width="100%" class="collapse">
                <tr class="background" >
                    <th class="border" width="50px" >CÓDIGO
                        PRODUCTO /
                        SERVICIO</th>
                    <th class="border">CANTIDAD</th>
                    <th class="border">UNIDAD DE
                        MEDIDA</th>
                    <th class="border">DESCRIPCIÓN</th>
                    <th class="border" width="60px">PRECIO
                        UNITARIO</th>
                    <th class="border">DESCUENTO</th>
                    <th class="border">SUBTOTAL</th>
                </tr>
                ' . $detalles . '
                <tr>
                    <td colspan="4"></td>
                    <td class="border right text-h5" colspan="2">SUBTOTAL Bs</td>
                    <td class="border right">' . $xml->cabecera->montoTotal . '</td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td class="border right text-h5" colspan="2">DESCUENTO Bs</td>
                    <td class="border right">0</td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td class="border right text-h5" colspan="2">TOTAL Bs</td>
                    <td class="border right">' . $xml->cabecera->montoTotal . '</td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td class="border right text-h5" colspan="2">MONTO GIFT CARD Bs</td>
                    <td class="border right">0</td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td class="border right text-h5 bold background" colspan="2">MONTO A PAGAR Bs</td>
                    <td class="border right">' . $xml->cabecera->montoTotal . '</td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td class="border right  text-h5 bold background" colspan="2">IMPORTE BASE CRÉDITO FISCAL Bs</td>
                    <td class="border right">' . $xml->cabecera->montoTotal . '</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="3" >
            <table width="100%">
                <tr>
                    <td class="center" valign=top>
                        <div class="left bold">Son: ' . $literal . '</div>
                        <div class="text-h5">ESTA FACTURA CONTRIBUYE AL DESARROLLO DEL PAÍS, EL USO ILÍCITO SERÁ SANCIONADO PENALMENTE DE ACUERDO A LEY
                            </div>
                        <div class="text-h5">' . $xml->cabecera->leyenda . '</div>
                        <div class="text-h2 bold">“'.$pieleyenda.'”</div>
                    </td>
                    <td>
                        <img width="95px" src="data:image/png;base64,' . $qrcode . '">
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
');
    }

}
