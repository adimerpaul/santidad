<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Cufd;
use App\Models\Cuis;
use App\Models\Sales;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class FacturacionSiatService
{
    public function __construct(private readonly SiatCodeService $siatCodeService) {}

    /**
     * Procesa la factura SIAT para una venta.
     * Retorna true si fue enviada en línea, false si quedó fuera de línea.
     * Nunca lanza excepciones — el fallo SIAT no debe anular la venta.
     */
    public function procesar(Sales $sales): bool
    {
        $sales->loadMissing(['details.product', 'client', 'user', 'agencia']);
        $client = $sales->client;

        if (!$client || $client->numeroDocumento === '0') {
            return false;
        }

        try {
            $cuiUltimo = Cuis::where('fechaVigencia', '>', date('Y-m-d H:i:s'))
                ->where('codigoSucursal', 0)
                ->where('codigoPuntoVenta', 0)
                ->latest('id')
                ->first();

            $cufdUltimo = Cufd::where('fechaVigencia', '>', date('Y-m-d H:i:s'))
                ->where('codigoSucursal', 0)
                ->where('codigoPuntoVenta', 0)
                ->latest('id')
                ->first();

            if (!$cuiUltimo || !$cufdUltimo) {
                error_log("SIAT: Sin CUIS/CUFD vigente para venta #{$sales->id}");
                $sales->siatEnviado = false;
                $sales->save();
                return false;
            }

            $codigoSucursal   = 0;
            $codigoPuntoVenta = 0;

            $fechaEmision = Carbon::now('America/La_Paz');
            $mili         = str_pad((string) ((int) floor(((int) $fechaEmision->format('u')) / 1000)), 3, '0', STR_PAD_LEFT);
            $fechaSiat    = $fechaEmision->format('Y-m-d\TH:i:s') . '.' . $mili;

            $numeroFactura = $sales->numeroFactura && (int) $sales->numeroFactura > 0
                ? (int) $sales->numeroFactura
                : ((int) Sales::max('numeroFactura') + 1);

            $leyenda = $this->leyendaSiat();

            $cuf = $this->generarCuf(
                (string) config('siat.nit'),
                $fechaEmision->format('YmdHis') . $mili,
                (string) $codigoSucursal,
                (string) config('siat.codigo_modalidad'),
                '1', '1', '1',
                (string) $numeroFactura,
                (string) $codigoPuntoVenta,
                (string) $cufdUltimo->codigoControl
            );

            $sales->numeroFactura         = $numeroFactura;
            $sales->venta                 = 'F';
            $sales->cuf                   = $cuf;
            $sales->cufd                  = $cufdUltimo->codigo;
            $sales->cui                   = $cuiUltimo->codigo;
            $sales->codigoSucursal        = $codigoSucursal;
            $sales->codigoPuntoVenta      = $codigoPuntoVenta;
            $sales->codigoDocumentoSector = 1;
            $sales->fechaEmision          = $fechaEmision->format('Y-m-d H:i:s');
            $sales->leyenda               = $leyenda;
            $sales->cufd_id               = $cufdUltimo->id;
            $sales->siatEnviado           = false;
            $sales->save();

            $xml = $this->buildSiatXml(
                $sales, $numeroFactura, $cuf,
                (string) $cufdUltimo->codigo,
                $codigoSucursal, $codigoPuntoVenta,
                $fechaSiat, $leyenda
            );

            $this->validateSiatXml($xml);

            $gzContent   = gzencode($xml, 9);
            $hashArchivo = hash('sha256', $gzContent);
            $this->storeSiatXml($sales->id, $xml, $gzContent);

            $payload = [
                'codigoAmbiente'        => (int) config('siat.codigo_ambiente'),
                'codigoDocumentoSector' => 1,
                'codigoEmision'         => 1,
                'codigoModalidad'       => (int) config('siat.codigo_modalidad'),
                'codigoPuntoVenta'      => $codigoPuntoVenta,
                'codigoSistema'         => (string) config('siat.codigo_sistema'),
                'codigoSucursal'        => $codigoSucursal,
                'cufd'                  => $cufdUltimo->codigo,
                'cuis'                  => $cuiUltimo->codigo,
                'nit'                   => (int) config('siat.nit'),
                'tipoFacturaDocumento'  => 1,
                'archivo'               => $gzContent,
                'fechaEnvio'            => $fechaSiat,
                'hashArchivo'           => $hashArchivo,
            ];

            $result      = $this->siatCodeService->recepcionFactura($payload);
            $transaccion = data_get($result, 'RespuestaServicioFacturacion.transaccion')
                ?? data_get($result, 'transaccion');

            if (!$transaccion) {
                error_log('SIAT rechazó factura: ' . json_encode($result));
                $sales->siatEnviado = false;
                $sales->save();
                return false;
            }

            $sales->codigoRecepcion = data_get($result, 'RespuestaServicioFacturacion.codigoRecepcion')
                ?? data_get($result, 'codigoRecepcion');
            $sales->siatEnviado = true;
            $sales->save();

            return true;

        } catch (\Throwable $e) {
            error_log("SIAT excepción venta #{$sales->id}: " . $e->getMessage());
            $sales->siatEnviado = false;
            $sales->save();
            return false;
        }
    }

    /**
     * Anula una factura en SIAT. Lanza excepción si el servicio rechaza.
     */
    public function anular(Sales $sale): void
    {
        if (!$sale->siatEnviado || $sale->siatAnulado || !$sale->cuf) {
            return;
        }

        $cufd = $sale->cufd_id
            ? Cufd::find($sale->cufd_id)
            : Cufd::where('codigo', $sale->cufd)->latest('id')->first();

        $cuis = Cuis::where('codigoSucursal', (int) ($sale->codigoSucursal ?? 0))
            ->where('codigoPuntoVenta', (int) ($sale->codigoPuntoVenta ?? 0))
            ->latest('id')
            ->first();

        if (!$cufd || !$cuis) {
            throw new \RuntimeException('No existe CUIS/CUFD para anular la factura en SIAT');
        }

        $payload = [
            'codigoAmbiente'        => (int) config('siat.codigo_ambiente'),
            'codigoDocumentoSector' => 1,
            'codigoEmision'         => 1,
            'codigoModalidad'       => (int) config('siat.codigo_modalidad'),
            'codigoPuntoVenta'      => (int) ($sale->codigoPuntoVenta ?? 0),
            'codigoSistema'         => (string) config('siat.codigo_sistema'),
            'codigoSucursal'        => (int) ($sale->codigoSucursal ?? 0),
            'cufd'                  => $cufd->codigo,
            'cuis'                  => $cuis->codigo,
            'nit'                   => (int) config('siat.nit'),
            'tipoFacturaDocumento'  => 1,
            'codigoMotivo'          => 1,
            'cuf'                   => $sale->cuf,
        ];

        $result      = $this->siatCodeService->anulacionFactura($payload);
        $transaccion = data_get($result, 'RespuestaServicioFacturacion.transaccion')
            ?? data_get($result, 'transaccion');

        if (!$transaccion) {
            throw new \RuntimeException(
                data_get($result, 'RespuestaServicioFacturacion.mensajesList.0.descripcion')
                ?? data_get($result, 'mensajesList.0.descripcion')
                ?? 'SIAT rechazo la anulacion de la factura'
            );
        }

        $sale->siatAnulado = true;
        $sale->save();
    }

    /**
     * Revierte la anulación de una factura en SIAT. Lanza excepción si el servicio rechaza.
     */
    public function revertirAnulacion(Sales $sale): void
    {
        if (!$sale->siatAnulado || !$sale->cuf) {
            throw new \RuntimeException('La factura no está anulada en SIAT o no tiene CUF');
        }

        $cufd = $sale->cufd_id
            ? Cufd::find($sale->cufd_id)
            : Cufd::where('codigo', $sale->cufd)->latest('id')->first();

        $cuis = Cuis::where('codigoSucursal', (int) ($sale->codigoSucursal ?? 0))
            ->where('codigoPuntoVenta', (int) ($sale->codigoPuntoVenta ?? 0))
            ->latest('id')
            ->first();

        if (!$cufd || !$cuis) {
            throw new \RuntimeException('No existe CUIS/CUFD para revertir la anulación en SIAT');
        }

        $payload = [
            'codigoAmbiente'        => (int) config('siat.codigo_ambiente'),
            'codigoDocumentoSector' => 1,
            'codigoEmision'         => 1,
            'codigoModalidad'       => (int) config('siat.codigo_modalidad'),
            'codigoPuntoVenta'      => (int) ($sale->codigoPuntoVenta ?? 0),
            'codigoSistema'         => (string) config('siat.codigo_sistema'),
            'codigoSucursal'        => (int) ($sale->codigoSucursal ?? 0),
            'cufd'                  => $cufd->codigo,
            'cuis'                  => $cuis->codigo,
            'nit'                   => (int) config('siat.nit'),
            'tipoFacturaDocumento'  => 1,
            'cuf'                   => $sale->cuf,
        ];

        $result      = $this->siatCodeService->reversionAnulacion($payload);
        $transaccion = data_get($result, 'RespuestaServicioFacturacion.transaccion')
            ?? data_get($result, 'transaccion');

        if (!$transaccion) {
            throw new \RuntimeException(
                data_get($result, 'RespuestaServicioFacturacion.mensajesList.0.descripcion')
                ?? data_get($result, 'mensajesList.0.descripcion')
                ?? 'SIAT rechazó la reversión de la anulación'
            );
        }

        $sale->siatAnulado = false;
        $sale->save();
    }

    /**
     * Envía una factura offline a SIAT como paquete (recepcionPaqueteFactura).
     * Flujo: registrar evento significativo → empaquetar XML → enviar → validar.
     */
    public function enviarPaquete(Sales $sale): void
    {
        if ($sale->siatEnviado) {
            throw new \RuntimeException('La factura ya fue enviada a SIAT');
        }

        $xmlPath = storage_path("app/siat/sales/{$sale->id}.xml");
        if (!file_exists($xmlPath)) {
            throw new \RuntimeException('No se encontró el XML de la factura para empaquetar');
        }

        $cufd = $sale->cufd_id
            ? Cufd::find($sale->cufd_id)
            : Cufd::where('codigo', $sale->cufd)->latest('id')->first();

        $cuis = Cuis::where('codigoSucursal', (int) ($sale->codigoSucursal ?? 0))
            ->where('codigoPuntoVenta', (int) ($sale->codigoPuntoVenta ?? 0))
            ->latest('id')
            ->first();

        if (!$cufd || !$cuis) {
            throw new \RuntimeException('No existe CUIS/CUFD para enviar el paquete');
        }

        $codigoSucursal   = (int) ($sale->codigoSucursal   ?? 0);
        $codigoPuntoVenta = (int) ($sale->codigoPuntoVenta ?? 0);

        // Ventana de evento: 1 segundo antes y 1 segundo después de la emisión
        $fechaEmision = Carbon::parse($sale->fechaEmision, 'America/La_Paz');
        $inicio = $fechaEmision->copy()->subSecond()->format('Y-m-d\TH:i:s.000');
        $fin    = $fechaEmision->copy()->addSecond()->format('Y-m-d\TH:i:s.999');

        $eventResult = $this->siatCodeService->registroEventoSignificativo([
            'codigoAmbiente'          => (int) config('siat.codigo_ambiente'),
            'codigoMotivoEvento'      => 1,
            'codigoPuntoVenta'        => $codigoPuntoVenta,
            'codigoSistema'           => (string) config('siat.codigo_sistema'),
            'codigoSucursal'          => $codigoSucursal,
            'cufd'                    => $cufd->codigo,
            'cufdEvento'              => $cufd->codigo,
            'cuis'                    => $cuis->codigo,
            'descripcion'             => 'Envio de factura generada fuera de linea',
            'fechaHoraFinEvento'      => $fin,
            'fechaHoraInicioEvento'   => $inicio,
            'nit'                     => (int) config('siat.nit'),
        ]);

        error_log("SIAT paquete [{$sale->id}] evento: " . json_encode($eventResult));

        $codigoEvento = data_get($eventResult, 'RespuestaListaEventos.codigoRecepcionEventoSignificativo')
            ?? data_get($eventResult, 'codigoRecepcionEventoSignificativo');

        if (!$codigoEvento) {
            throw new \RuntimeException('SIAT no devolvió código de evento: ' . json_encode($eventResult));
        }

        // Construir tar.gz con el XML de la factura
        $tempDir = storage_path("app/siat/temp/{$sale->id}");
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $xmlTemp = "{$tempDir}/{$sale->id}.xml";
        $tarPath = "{$tempDir}/paquete.tar";

        try {
            copy($xmlPath, $xmlTemp);

            $phar = new \PharData($tarPath);
            $phar->addFile($xmlTemp, "{$sale->id}.xml");
            $phar->compress(\Phar::GZ);

            $gzPath    = $tarPath . '.gz';
            $gzContent = file_get_contents($gzPath);
            $hash      = hash('sha256', $gzContent);
            $fechaEnvio = Carbon::now('America/La_Paz')->format('Y-m-d\TH:i:s.000');

            $packagePayload = [
                'codigoAmbiente'        => (int) config('siat.codigo_ambiente'),
                'codigoDocumentoSector' => 1,
                'codigoEmision'         => 2,
                'codigoModalidad'       => (int) config('siat.codigo_modalidad'),
                'codigoPuntoVenta'      => $codigoPuntoVenta,
                'codigoSistema'         => (string) config('siat.codigo_sistema'),
                'codigoSucursal'        => $codigoSucursal,
                'cufd'                  => $cufd->codigo,
                'cuis'                  => $cuis->codigo,
                'nit'                   => (int) config('siat.nit'),
                'tipoFacturaDocumento'  => 1,
                'archivo'               => $gzContent,
                'fechaEnvio'            => $fechaEnvio,
                'hashArchivo'           => $hash,
                'cantidadFacturas'      => 1,
                'codigoEvento'          => (int) $codigoEvento,
            ];

            $recepcionResult = $this->siatCodeService->recepcionPaqueteFactura($packagePayload);
            error_log("SIAT paquete [{$sale->id}] recepcion: " . json_encode($recepcionResult));

            $codigoRecepcion = data_get($recepcionResult, 'RespuestaServicioFacturacion.codigoRecepcion')
                ?? data_get($recepcionResult, 'codigoRecepcion');

            if (!$codigoRecepcion) {
                $mensajesSiat = data_get($recepcionResult, 'RespuestaServicioFacturacion.mensajesList')
                    ?? data_get($recepcionResult, 'mensajesList')
                    ?? [];
                throw new \RuntimeException(
                    'SIAT no devolvió código de recepción. ' .
                    (is_array($mensajesSiat) ? json_encode($mensajesSiat) : (string) $mensajesSiat)
                );
            }

            // Polling de validación (máx. 10 intentos con 1 s de pausa)
            $validado    = false;
            $intentos    = 0;
            $maxIntentos = 10;
            $ultimaRespuesta = [];

            $validationPayload = [
                'codigoAmbiente'        => (int) config('siat.codigo_ambiente'),
                'codigoDocumentoSector' => 1,
                'codigoEmision'         => 2,
                'codigoModalidad'       => (int) config('siat.codigo_modalidad'),
                'codigoPuntoVenta'      => $codigoPuntoVenta,
                'codigoSistema'         => (string) config('siat.codigo_sistema'),
                'codigoSucursal'        => $codigoSucursal,
                'cufd'                  => $cufd->codigo,
                'cuis'                  => $cuis->codigo,
                'nit'                   => (int) config('siat.nit'),
                'tipoFacturaDocumento'  => 1,
                'codigoRecepcion'       => $codigoRecepcion,
            ];

            while (!$validado && $intentos < $maxIntentos) {
                sleep(1);
                $valResult       = $this->siatCodeService->validacionRecepcionPaqueteFactura($validationPayload);
                $ultimaRespuesta = $valResult;
                error_log("SIAT paquete [{$sale->id}] validacion intento {$intentos}: " . json_encode($valResult));

                $descripcion = data_get($valResult, 'RespuestaServicioFacturacion.codigoDescripcion')
                    ?? data_get($valResult, 'codigoDescripcion');

                if ($descripcion === 'VALIDADA') {
                    $validado = true;
                }
                $intentos++;
            }

            if (!$validado) {
                $mensajesSiat = data_get($ultimaRespuesta, 'RespuestaServicioFacturacion.mensajesList')
                    ?? data_get($ultimaRespuesta, 'mensajesList')
                    ?? [];
                $detalle = is_array($mensajesSiat) ? json_encode($mensajesSiat) : (string) $mensajesSiat;
                throw new \RuntimeException("SIAT no validó el paquete tras {$maxIntentos} intentos. Última respuesta: {$detalle}");
            }

            $sale->siatEnviado                         = true;
            $sale->codigoRecepcion                     = $codigoRecepcion;
            $sale->codigoRecepcionEventoSignificativo  = $codigoEvento;
            $sale->save();

        } finally {
            @unlink($xmlTemp);
            @unlink($tarPath);
            @unlink($tarPath . '.gz');
            @rmdir($tempDir);
        }
    }

    // ─────────────────────────── XML ───────────────────────────

    private function buildSiatXml(
        Sales  $sale,
        int    $numeroFactura,
        string $cuf,
        string $cufd,
        int    $codigoSucursal,
        int    $codigoPuntoVenta,
        string $fechaSiat,
        string $leyenda
    ): string {
        $client = $sale->client ?: new Client([
            'nombreRazonSocial'            => 'SIN NOMBRE',
            'numeroDocumento'              => '0',
            'codigoTipoDocumentoIdentidad' => 1,
            'complemento'                  => null,
        ]);

        $detalles = '';
        foreach ($sale->details as $detail) {
            $actividadEconomica = $detail->actividadEconomica ?: '4772100';
            $codigoProductoSin  = $detail->codigoProductoSin  ?: '1003655';
            $codigoProducto     = $detail->product_id          ?: 0;
            $descripcion        = $this->xmlValue($detail->descripcion ?: 'PRODUCTO');
            $cantidad           = number_format((float) $detail->cantidad, 2, '.', '');
            $precioUnitario     = number_format((float) $detail->precioUnitario, 2, '.', '');
            $subTotal           = number_format((float) $detail->subTotal, 2, '.', '');

            $detalles .= <<<XML
    <detalle>
        <actividadEconomica>{$actividadEconomica}</actividadEconomica>
        <codigoProductoSin>{$codigoProductoSin}</codigoProductoSin>
        <codigoProducto>{$codigoProducto}</codigoProducto>
        <descripcion>{$descripcion}</descripcion>
        <cantidad>{$cantidad}</cantidad>
        <unidadMedida>1</unidadMedida>
        <precioUnitario>{$precioUnitario}</precioUnitario>
        <montoDescuento>0</montoDescuento>
        <subTotal>{$subTotal}</subTotal>
        <numeroSerie xsi:nil="true"/>
        <numeroImei xsi:nil="true"/>
    </detalle>\n
XML;
        }

        $razon             = $this->xmlValue((string) env('RAZON', 'Santidad Divina'));
        $nit               = $this->xmlValue((string) config('siat.nit'));
        $municipio         = $this->xmlValue((string) env('MUNICIPIO', 'Oruro'));
        $telefono          = $this->xmlValue((string) env('TELEFONO', ''));
        $direccion         = $this->xmlValue((string) env('DIRECCION', ''));
        $nombreRazonSocial = $this->xmlValue((string) ($client->nombreRazonSocial ?: 'SIN NOMBRE'));
        $numeroDocumento   = $this->xmlValue((string) ($client->numeroDocumento   ?: '0'));
        $codigoCliente     = $client->id ?: 0;
        $codigoMetodoPago  = $this->codigoMetodoPago($sale->metodoPago);
        $montoTotal        = number_format((float) $sale->montoTotal, 2, '.', '');

        $descuentoAdicional = $sale->descuento && (float) $sale->descuento > 0
            ? '<descuentoAdicional>' . number_format((float) $sale->descuento, 2, '.', '') . '</descuentoAdicional>'
            : '<descuentoAdicional xsi:nil="true"/>';

        $complementoXml = $client->complemento
            ? '<complemento>' . $this->xmlValue((string) $client->complemento) . '</complemento>'
            : '<complemento xsi:nil="true"/>';

        $usuario    = $this->xmlValue((string) ($sale->user?->name ?: $sale->usuario ?: 'admin'));
        $leyendaXml = $this->xmlValue(mb_substr($leyenda, 0, 200));

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<facturaComputarizadaCompraVenta xsi:noNamespaceSchemaLocation="facturaComputarizadaCompraVenta.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <cabecera>
        <nitEmisor>{$nit}</nitEmisor>
        <razonSocialEmisor>{$razon}</razonSocialEmisor>
        <municipio>{$municipio}</municipio>
        <telefono>{$telefono}</telefono>
        <numeroFactura>{$numeroFactura}</numeroFactura>
        <cuf>{$cuf}</cuf>
        <cufd>{$cufd}</cufd>
        <codigoSucursal>{$codigoSucursal}</codigoSucursal>
        <direccion>{$direccion}</direccion>
        <codigoPuntoVenta>{$codigoPuntoVenta}</codigoPuntoVenta>
        <fechaEmision>{$fechaSiat}</fechaEmision>
        <nombreRazonSocial>{$nombreRazonSocial}</nombreRazonSocial>
        <codigoTipoDocumentoIdentidad>{$client->codigoTipoDocumentoIdentidad}</codigoTipoDocumentoIdentidad>
        <numeroDocumento>{$numeroDocumento}</numeroDocumento>
        {$complementoXml}
        <codigoCliente>{$codigoCliente}</codigoCliente>
        <codigoMetodoPago>{$codigoMetodoPago}</codigoMetodoPago>
        <numeroTarjeta xsi:nil="true"/>
        <montoTotal>{$montoTotal}</montoTotal>
        <montoTotalSujetoIva>{$montoTotal}</montoTotalSujetoIva>
        <codigoMoneda>1</codigoMoneda>
        <tipoCambio>1</tipoCambio>
        <montoTotalMoneda>{$montoTotal}</montoTotalMoneda>
        <montoGiftCard xsi:nil="true"/>
        {$descuentoAdicional}
        <codigoExcepcion xsi:nil="true"/>
        <cafc xsi:nil="true"/>
        <leyenda>{$leyendaXml}</leyenda>
        <usuario>{$usuario}</usuario>
        <codigoDocumentoSector>1</codigoDocumentoSector>
    </cabecera>
{$detalles}</facturaComputarizadaCompraVenta>
XML;
    }

    private function validateSiatXml(string $xml): void
    {
        $schemaPath = realpath(base_path('../siat/facturaComputarizadaCompraVenta.xsd'));
        if (!$schemaPath || !is_file($schemaPath)) {
            throw new \RuntimeException('No se encontro el XSD de SIAT para validar la factura');
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        if (!$document->loadXML($xml)) {
            $errors  = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors(false);
            $message = $errors ? trim($errors[0]->message) : 'No se pudo cargar el XML';
            throw new \RuntimeException('XML SIAT invalido: ' . $message);
        }

        $valid  = $document->schemaValidate($schemaPath);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        if ($valid) {
            return;
        }

        $messages = array_map(
            static fn ($e) => trim($e->message) . ' (linea ' . $e->line . ')',
            $errors
        );

        throw new \RuntimeException(
            'XML SIAT invalido: ' . implode(' | ', $messages ?: ['schemaValidate invalid'])
        );
    }

    private function storeSiatXml(int $saleId, string $xml, string $gzContent): void
    {
        try {
            Storage::disk('local')->put("siat/sales/{$saleId}.xml", $xml);
            Storage::disk('local')->put("siat/sales/{$saleId}.xml.gz", $gzContent);
        } catch (\Throwable $e) {
            error_log('Error guardando XML SIAT: ' . $e->getMessage());
        }
    }

    // ─────────────────────────── Helpers ───────────────────────────

    private function leyendaSiat(): string
    {
        $leyendas = [
            'Ley N° 453: Puedes acceder a la reclamacion cuando tus derechos han sido vulnerados.',
            'Ley N° 453: El proveedor debe brindar atencion sin discriminacion, con respeto, calidez y cordialidad a los usuarios y consumidores.',
        ];
        return $leyendas[array_rand($leyendas)];
    }

    private function codigoMetodoPago(?string $metodoPago): int
    {
        return match ($metodoPago) {
            'Tarjeta'      => 2,
            'Transferencia' => 3,
            'Qr', 'QR'    => 16,
            default        => 1,
        };
    }

    private function xmlValue(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function generarCuf(
        string $nit,
        string $fechaHora,
        string $sucursal,
        string $modalidad,
        string $tipoEmision,
        string $codigoDocumentoFiscal,
        string $tipoDocumentoSector,
        string $numeroFactura,
        string $puntoVenta,
        string $codigoControl
    ): string {
        $cadena  = str_pad($nit, 13, '0', STR_PAD_LEFT);
        $cadena .= $fechaHora;
        $cadena .= str_pad($sucursal, 4, '0', STR_PAD_LEFT);
        $cadena .= $modalidad;
        $cadena .= $tipoEmision;
        $cadena .= $codigoDocumentoFiscal;
        $cadena .= str_pad($tipoDocumentoSector, 2, '0', STR_PAD_LEFT);
        $cadena .= str_pad($numeroFactura, 10, '0', STR_PAD_LEFT);
        $cadena .= str_pad($puntoVenta, 4, '0', STR_PAD_LEFT);
        $cadena .= $this->calculaDigitoMod11($cadena, 1, 9, false);

        return $this->base16($cadena) . $codigoControl;
    }

    private function calculaDigitoMod11(string $dado, int $numDig, int $limMult, bool $x10): string
    {
        if (!$x10) {
            $numDig = 1;
        }

        for ($n = 1; $n <= $numDig; $n++) {
            $soma = 0;
            $mult = 2;
            for ($i = strlen($dado) - 1; $i >= 0; $i--) {
                $soma += $mult * (int) $dado[$i];
                if (++$mult > $limMult) {
                    $mult = 2;
                }
            }
            $dig    = $x10 ? (($soma * 10) % 11) % 10 : $soma % 11;
            $dado  .= $dig === 10 ? '1' : ($dig === 11 ? '0' : (string) $dig);
        }

        return substr($dado, strlen($dado) - $numDig, $numDig);
    }

    private function base16(string $number): string
    {
        $hexvalues = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F'];
        $hexval    = '';

        while ($number !== '0') {
            $hexval  = $hexvalues[(int) bcmod($number, '16')] . $hexval;
            $number  = bcdiv($number, '16', 0);
        }

        return $hexval;
    }
}
