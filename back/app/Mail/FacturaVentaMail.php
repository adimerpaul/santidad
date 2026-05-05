<?php

namespace App\Mail;

use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Luecano\NumeroALetras\NumeroALetras;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class FacturaVentaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly array $details) {}

    public function build(): static
    {
        $saleId  = $this->details['sale_id'];
        $online  = $this->details['online'] ?? false;
        $xmlPath = storage_path("app/siat/sales/{$saleId}.xml");
        $pdfPath = storage_path("app/siat/sales/{$saleId}.pdf");

        if (file_exists($xmlPath)) {
            try {
                $html = $this->generatePdfHtml($xmlPath, $online);
                $dompdf = new Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('letter');
                $dompdf->render();
                file_put_contents($pdfPath, $dompdf->output());
            } catch (\Throwable $e) {
                error_log("FacturaVentaMail: error generando PDF para venta {$saleId}: " . $e->getMessage());
            }
        }

        $mail = $this
            ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'))
            ->subject('Su Factura - ' . env('RAZON', 'Santidad Divina'))
            ->view('emails.factura_venta')
            ->with(['online' => $online]);

        if (file_exists($pdfPath)) {
            $mail->attach($pdfPath, [
                'as'   => "Factura_{$saleId}.pdf",
                'mime' => 'application/pdf',
            ]);
        }

        if (file_exists($xmlPath)) {
            $mail->attach($xmlPath, [
                'as'   => "Factura_{$saleId}.xml",
                'mime' => 'application/xml',
            ]);
        }

        return $mail;
    }

    private function generatePdfHtml(string $xmlPath, bool $online): string
    {
        $xml = simplexml_load_string(file_get_contents($xmlPath));

        // CUF con saltos cada 20 caracteres
        $cuf    = '';
        $rawCuf = (string) $xml->cabecera->cuf;
        for ($i = 0; $i < strlen($rawCuf); $i++) {
            $cuf .= $rawCuf[$i];
            if (($i + 1) % 20 === 0 && $i < strlen($rawCuf) - 1) {
                $cuf .= '<br>';
            }
        }

        // Fecha formateada
        try {
            $fechaCarbon     = Carbon::parse((string) $xml->cabecera->fechaEmision);
            $fechaFormateada = $fechaCarbon->format('d/m/Y') . ' ' . $fechaCarbon->format('h:i A');
        } catch (\Exception $e) {
            $fechaFormateada = substr((string) $xml->cabecera->fechaEmision, 0, 16);
        }

        // Monto en letras
        $formatter = new NumeroALetras();
        $literal   = $formatter->toInvoice((float) $xml->cabecera->montoTotal, 2, 'Bolivianos');

        // Filas de detalle
        $detalles = '';
        foreach ($xml->detalle as $d) {
            $detalles .= '
            <tr>
                <td class="border center">' . (string) $d->codigoProducto . '</td>
                <td class="border right">'  . number_format((float) $d->cantidad, 2) . '</td>
                <td class="border center">' . $this->codigoUnidad((string) $d->unidadMedida) . '</td>
                <td class="border">'        . htmlspecialchars((string) $d->descripcion, ENT_QUOTES) . '</td>
                <td class="border right">'  . number_format((float) $d->precioUnitario, 2) . '</td>
                <td class="border right">0.00</td>
                <td class="border right">'  . number_format((float) $d->subTotal, 2) . '</td>
            </tr>';
        }

        // QR
        $qrUrl  = env('URL_SIAT2', 'https://siat.impuestos.gob.bo/')
            . 'consulta/QR?nit=' . env('NIT')
            . '&cuf=' . (string) $xml->cabecera->cuf
            . '&numero=' . (string) $xml->cabecera->numeroFactura
            . '&t=2';
        $qrcode = base64_encode(QrCode::format('svg')->size(200)->errorCorrection('H')->generate($qrUrl));

        // Documento del cliente
        $numDoc = (string) $xml->cabecera->numeroDocumento;
        $comp   = (string) $xml->cabecera->complemento;
        $doc    = ($comp !== '') ? "{$numDoc}-{$comp}" : $numDoc;

        // Texto pie de página
        $pieleyenda = $online
            ? '"Este documento es la Representación Gráfica de un Documento Fiscal Digital emitido en una modalidad de facturación en línea"'
            : '"Este documento es la Representación Gráfica de un Documento Fiscal Digital emitido fuera de línea, verifique su envío con su proveedor o en la página web www.impuestos.gob.bo"';

        $montoTotal = number_format((float) $xml->cabecera->montoTotal, 2);

        return <<<HTML
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { font-family: Arial, sans-serif; font-size: 12px; }
.bold { font-weight: bold; }
.title { font-size: 18px; }
.sm { font-size: 10px; }
.xs { font-size: 8px; }
.center { text-align: center; }
.right { text-align: right; }
.border { border: 1px solid black; padding: 2px 4px; }
.collapse { border-collapse: collapse; }
.bg-gray { background-color: #e2e8f0; }
.blue { color: #2b6cb0; }
.orange { color: #c05621; }

</style>
</head>
<body>
<table width="100%" class="collapse">
    <tr>
        <td width="40%" valign="top">
            <div class="bold center">{$xml->cabecera->razonSocialEmisor}</div>
            <div class="bold center">CASA MATRIZ</div>
            <div class="center">No. Punto de Venta {$xml->cabecera->codigoPuntoVenta}</div>
            <div class="center">{$xml->cabecera->direccion}</div>
            <div class="center">Teléfono: {$xml->cabecera->telefono}</div>
            <div class="center orange">{$xml->cabecera->municipio}</div>
        </td>
        <td width="20%"></td>
        <td width="40%" valign="top">
            <table>
                <tr>
                    <td valign="top" width="120px"><div class="bold">NIT</div></td>
                    <td><div>{$xml->cabecera->nitEmisor}</div></td>
                </tr>
                <tr>
                    <td valign="top"><div class="bold">FACTURA N°</div></td>
                    <td><div>{$xml->cabecera->numeroFactura}</div></td>
                </tr>
                <tr>
                    <td valign="top"><div class="bold">CÓD. AUTORIZACIÓN</div></td>
                    <td><div style="width:130px; word-break:break-all;">{$cuf}</div></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="3" style="padding:8px 0;">
            <div class="bold title center">FACTURA</div>
            <div class="center">(Con Derecho a Crédito Fiscal)</div>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <table>
                <tr>
                    <td><div class="bold">Fecha:</div></td>
                    <td><div>{$fechaFormateada}</div></td>
                </tr>
                <tr>
                    <td><div class="bold">Nombre/Razón Social:</div></td>
                    <td><div>{$xml->cabecera->nombreRazonSocial}</div></td>
                </tr>
            </table>
        </td>
        <td>
            <table>
                <tr>
                    <td class="right"><div class="bold">NIT/CI/CEX:</div></td>
                    <td><div>{$doc}</div></td>
                </tr>
                <tr>
                    <td class="right"><div class="bold">Cod. Cliente:</div></td>
                    <td><div>{$xml->cabecera->codigoCliente}</div></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <table width="100%" class="collapse" style="margin-top:6px;">
                <tr class="bg-gray">
                    <th class="border" width="60px">CÓDIGO<br>PRODUCTO /<br>SERVICIO</th>
                    <th class="border" width="55px">CANTIDAD</th>
                    <th class="border" width="70px">UNIDAD DE<br>MEDIDA</th>
                    <th class="border">DESCRIPCIÓN</th>
                    <th class="border" width="65px">PRECIO<br>UNITARIO</th>
                    <th class="border" width="65px">DESCUENTO</th>
                    <th class="border" width="65px">SUBTOTAL</th>
                </tr>
                {$detalles}
                <tr>
                    <td colspan="4"></td>
                    <td class="border right xs" colspan="2">SUBTOTAL Bs</td>
                    <td class="border right">{$montoTotal}</td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td class="border right xs" colspan="2">DESCUENTO Bs</td>
                    <td class="border right">0.00</td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td class="border right xs" colspan="2">TOTAL Bs</td>
                    <td class="border right">{$montoTotal}</td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td class="border right xs" colspan="2">MONTO GIFT CARD Bs</td>
                    <td class="border right">0.00</td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td class="border right xs bold bg-gray" colspan="2">MONTO A PAGAR Bs</td>
                    <td class="border right">{$montoTotal}</td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td class="border right xs bold bg-gray" colspan="2">IMPORTE BASE CRÉDITO FISCAL Bs</td>
                    <td class="border right">{$montoTotal}</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <table width="100%" style="margin-top:8px;">
                <tr>
                    <td valign="top">
                        <div class="bold">Son: {$literal}</div>
                        <br>
                        <div class="xs bold blue">ESTA FACTURA CONTRIBUYE AL DESARROLLO DEL PAÍS, EL USO ILÍCITO SERÁ SANCIONADO PENALMENTE DE ACUERDO A LEY</div>
                        <div class="xs orange">{$xml->cabecera->leyenda}</div>
                        <div class="sm bold">{$pieleyenda}</div>
                    </td>
                    <td valign="top" width="110px">
                        <img width="95px" src="data:image/svg+xml;base64,{$qrcode}">
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
HTML;
    }

    private function codigoUnidad(string $codigo): string
    {
        $map = [
            '1'  => 'Unidad',
            '2'  => 'Gramos',
            '3'  => 'Kilogramos',
            '4'  => 'Toneladas',
            '5'  => 'Mililitros',
            '6'  => 'Litros',
            '7'  => 'Metros',
            '8'  => 'Metros Cuadrados',
            '9'  => 'Metros Cúbicos',
            '10' => 'Bobinas',
            '33' => 'Cápsulas',
            '34' => 'Tabletas',
            '35' => 'Comprimidos',
            '58' => 'Piezas',
        ];
        return $map[$codigo] ?? "Ud.({$codigo})";
    }
}
