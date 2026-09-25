<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Cufd;
use App\Models\Cuis;
use App\Models\Sales;
use App\Models\SiatEnvio;
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
        $sales->loadMissing(['details.product', 'client', 'user.agencia', 'agencia']);
        $client = $sales->client;

        if (!$client || $client->numeroDocumento === '0') {
            return false;
        }

        try {
            $codigoSucursal   = (int) ($sales->agencia?->sucursal ?? $sales->user?->agencia?->sucursal ?? 0);
            $codigoPuntoVenta = 0;

            // Sucursal 0 = agencia no habilitada para facturar en SIAT. La venta
            // se queda como nota de venta: sin factura, sin XML y sin correo.
            if ($codigoSucursal === 0) {
                return false;
            }

            // El CUFD caduca cada día: si no hay uno vigente se pide al vuelo a
            // SIAT (junto con el CUIS si también venció) en lugar de rechazar.
            $cuiUltimo  = $this->asegurarCuisVigente($codigoSucursal, $codigoPuntoVenta);
            $cufdUltimo = $cuiUltimo
                ? $this->asegurarCufdVigente($codigoSucursal, $codigoPuntoVenta)
                : null;

            if (!$cuiUltimo || !$cufdUltimo) {
                error_log("SIAT: Sin CUIS/CUFD vigente para sucursal {$codigoSucursal}, venta #{$sales->id}");
                $sales->siatEnviado = false;
                $sales->save();
                return false;
            }

            $result = $this->emitirFactura($sales, $cuiUltimo, $cufdUltimo, $codigoSucursal, $codigoPuntoVenta);

            // SIAT invalida el CUFD anterior cada vez que se solicita uno nuevo
            // (otro terminal, otro sistema o el servidor pueden haberlo rotado).
            // Si rechaza por CUF/CUFD desactualizado se renueva y se reintenta una vez.
            if (!$this->esRespuestaAceptada($result) && $this->rechazoPorCufdDesactualizado($result)) {
                error_log("SIAT: CUFD desactualizado en venta #{$sales->id}, solicitando uno nuevo y reintentando");
                $cufdNuevo = $this->renovarCufd($cuiUltimo, $codigoSucursal, $codigoPuntoVenta);

                if ($cufdNuevo) {
                    $result = $this->emitirFactura($sales, $cuiUltimo, $cufdNuevo, $codigoSucursal, $codigoPuntoVenta);
                }
            }

            if (!$this->esRespuestaAceptada($result)) {
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
     * Arma el XML, lo firma con el CUF del CUFD recibido y lo envía a SIAT.
     * Devuelve la respuesta cruda del servicio.
     * Con $reemision la fecha declarada a SIAT se guarda en `fechaEnvioFactura`
     * y `fechaEmision` (fecha de caja) no se toca.
     */
    private function emitirFactura(
        Sales $sales,
        Cuis $cuis,
        Cufd $cufd,
        int $codigoSucursal,
        int $codigoPuntoVenta,
        bool $reemision = false
    ): array {
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
            (string) $cufd->codigoControl
        );

        $sales->numeroFactura         = $numeroFactura;
        $sales->venta                 = 'F';
        $sales->cuf                   = $cuf;
        $sales->cufd                  = $cufd->codigo;
        $sales->cui                   = $cuis->codigo;
        $sales->codigoSucursal        = $codigoSucursal;
        $sales->codigoPuntoVenta      = $codigoPuntoVenta;
        $sales->codigoDocumentoSector = 1;
        if ($reemision) {
            $sales->fechaEnvioFactura = $fechaEmision->format('Y-m-d H:i:s');
        } else {
            $sales->fechaEmision      = $fechaEmision->format('Y-m-d H:i:s');
        }
        $sales->leyenda               = $leyenda;
        $sales->cufd_id               = $cufd->id;
        $sales->siatEnviado           = false;
        $sales->save();

        $xml = $this->buildSiatXml(
            $sales, $numeroFactura, $cuf,
            (string) $cufd->codigo,
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
            'cufd'                  => $cufd->codigo,
            'cuis'                  => $cuis->codigo,
            'nit'                   => (int) config('siat.nit'),
            'tipoFacturaDocumento'  => 1,
            'archivo'               => $gzContent,
            'fechaEnvio'            => $fechaSiat,
            'hashArchivo'           => $hashArchivo,
        ];

        return $this->siatCodeService->recepcionFactura($payload);
    }

    private function esRespuestaAceptada(array $result): bool
    {
        return (bool) (data_get($result, 'RespuestaServicioFacturacion.transaccion')
            ?? data_get($result, 'transaccion'));
    }

    /**
     * 914/1003 = CUFD inválido, 1002 = CUF inválido. Aparecen cuando SIAT ya
     * emitió un CUFD posterior al que tenemos guardado.
     */
    private function rechazoPorCufdDesactualizado(array $result): bool
    {
        $mensajes = data_get($result, 'RespuestaServicioFacturacion.mensajesList')
            ?? data_get($result, 'mensajesList')
            ?? [];

        if (isset($mensajes['codigo'])) {
            $mensajes = [$mensajes];
        }

        foreach ($mensajes as $mensaje) {
            if (in_array((int) data_get($mensaje, 'codigo'), [914, 1002, 1003], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Solicita un CUFD nuevo a SIAT y lo guarda. El CUFD vigente pasa a ser este.
     */
    public function renovarCufd(Cuis $cuis, int $codigoSucursal, int $codigoPuntoVenta): ?Cufd
    {
        $respuesta = $this->siatCodeService->solicitarCufd([
            'codigoAmbiente'   => (int) config('siat.codigo_ambiente'),
            'codigoModalidad'  => (int) config('siat.codigo_modalidad'),
            'codigoPuntoVenta' => $codigoPuntoVenta,
            'codigoSistema'    => (string) config('siat.codigo_sistema'),
            'codigoSucursal'   => $codigoSucursal,
            'cuis'             => $cuis->codigo,
            'nit'              => (int) config('siat.nit'),
        ]);

        $normalizada = $respuesta['RespuestaCufd'] ?? $respuesta;

        if (empty($normalizada['codigo'])) {
            error_log('SIAT no devolvió un CUFD válido: ' . json_encode($respuesta));
            return null;
        }

        return Cufd::create([
            'codigo'           => $normalizada['codigo'],
            'codigoControl'    => $normalizada['codigoControl'] ?? null,
            'direccion'        => config('app.url'),
            'fechaVigencia'    => Carbon::now()->endOfDay(),
            'fechaCreacion'    => Carbon::now(),
            'codigoPuntoVenta' => $codigoPuntoVenta,
            'codigoSucursal'   => $codigoSucursal,
        ]);
    }

    /**
     * Devuelve el CUIS vigente del punto de venta y, si no hay, lo solicita a
     * SIAT y lo guarda. Devuelve null si SIAT no responde con un código válido.
     */
    public function asegurarCuisVigente(int $codigoSucursal, int $codigoPuntoVenta = 0): ?Cuis
    {
        $cuis = Cuis::where('fechaVigencia', '>', date('Y-m-d H:i:s'))
            ->where('codigoSucursal', $codigoSucursal)
            ->where('codigoPuntoVenta', $codigoPuntoVenta)
            ->latest('id')
            ->first();

        if ($cuis) {
            return $cuis;
        }

        try {
            $respuesta = $this->siatCodeService->solicitarCuis([
                'codigoAmbiente'   => (int) config('siat.codigo_ambiente'),
                'codigoModalidad'  => (int) config('siat.codigo_modalidad'),
                'codigoPuntoVenta' => $codigoPuntoVenta,
                'codigoSistema'    => (string) config('siat.codigo_sistema'),
                'codigoSucursal'   => $codigoSucursal,
                'nit'              => (int) config('siat.nit'),
            ]);

            $normalizada = $respuesta['RespuestaCuis'] ?? $respuesta;

            if (empty($normalizada['codigo'])) {
                error_log('SIAT no devolvió un CUIS válido: ' . json_encode($respuesta));
                return null;
            }

            return Cuis::create([
                'codigo'           => $normalizada['codigo'],
                'fechaVigencia'    => $normalizada['fechaVigencia'] ?? Carbon::now()->addYear(),
                'fechaCreacion'    => Carbon::now(),
                'codigoPuntoVenta' => $codigoPuntoVenta,
                'codigoSucursal'   => $codigoSucursal,
            ]);
        } catch (\Throwable $e) {
            error_log("SIAT: no se pudo solicitar el CUIS de la sucursal {$codigoSucursal}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Devuelve el CUFD vigente del punto de venta y, si no hay, lo solicita a
     * SIAT y lo guarda. Devuelve null si SIAT no responde con un código válido.
     */
    public function asegurarCufdVigente(int $codigoSucursal, int $codigoPuntoVenta = 0): ?Cufd
    {
        $cufd = Cufd::where('fechaVigencia', '>', date('Y-m-d H:i:s'))
            ->where('codigoSucursal', $codigoSucursal)
            ->where('codigoPuntoVenta', $codigoPuntoVenta)
            ->latest('id')
            ->first();

        if ($cufd) {
            return $cufd;
        }

        $cuis = $this->asegurarCuisVigente($codigoSucursal, $codigoPuntoVenta);

        if (!$cuis) {
            return null;
        }

        try {
            return $this->renovarCufd($cuis, $codigoSucursal, $codigoPuntoVenta);
        } catch (\Throwable $e) {
            error_log("SIAT: no se pudo solicitar el CUFD de la sucursal {$codigoSucursal}: " . $e->getMessage());
            return null;
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
     * Envía a SIAT una factura pendiente (quedó fuera de línea al venderse).
     * El CUFD con el que se generó suele estar vencido o rotado y SIAT lo
     * rechaza (914), así que la factura se reemite en línea con la fecha de
     * hoy y un CUFD vigente. La fecha declarada queda en `fechaEnvioFactura`;
     * `fechaEmision` se mantiene para no mover la venta de caja.
     */
    public function enviarPaquete(Sales $sale): void
    {
        if ($sale->siatEnviado) {
            throw new \RuntimeException('La factura ya fue enviada a SIAT');
        }

        $sale->loadMissing(['details.product', 'client', 'user.agencia', 'agencia']);

        $codigoSucursal = (int) ($sale->codigoSucursal
            ?: $sale->agencia?->sucursal
            ?: $sale->user?->agencia?->sucursal
            ?: 0);
        $codigoPuntoVenta = (int) ($sale->codigoPuntoVenta ?? 0);

        if ($codigoSucursal === 0) {
            throw new \RuntimeException('La agencia de la venta no está habilitada para facturar en SIAT');
        }

        $cuis = $this->asegurarCuisVigente($codigoSucursal, $codigoPuntoVenta);
        $cufd = $cuis ? $this->asegurarCufdVigente($codigoSucursal, $codigoPuntoVenta) : null;

        if (!$cuis || !$cufd) {
            throw new \RuntimeException('No se pudo obtener CUIS/CUFD vigente de SIAT');
        }

        $envio = SiatEnvio::firstOrCreate(
            ['sale_id' => $sale->id],
            ['estado'  => 'pendiente']
        );

        $result = $this->emitirFactura($sale, $cuis, $cufd, $codigoSucursal, $codigoPuntoVenta, true);

        // El CUFD guardado como vigente puede haber sido rotado en SIAT: se
        // pide uno nuevo y se reintenta una vez.
        if (!$this->esRespuestaAceptada($result) && $this->rechazoPorCufdDesactualizado($result)) {
            error_log("SIAT reemisión [{$sale->id}] CUFD desactualizado, solicitando uno nuevo");
            $cufdNuevo = $this->renovarCufd($cuis, $codigoSucursal, $codigoPuntoVenta);

            if ($cufdNuevo) {
                $result = $this->emitirFactura($sale, $cuis, $cufdNuevo, $codigoSucursal, $codigoPuntoVenta, true);
            }
        }

        error_log("SIAT reemisión [{$sale->id}]: " . json_encode($result));

        if (!$this->esRespuestaAceptada($result)) {
            $mensajesSiat = data_get($result, 'RespuestaServicioFacturacion.mensajesList')
                ?? data_get($result, 'mensajesList')
                ?? [];
            $detalle = is_array($mensajesSiat) ? json_encode($mensajesSiat) : (string) $mensajesSiat;
            $envio->update(['estado' => 'error', 'ultimo_mensaje' => $detalle]);
            throw new \RuntimeException('SIAT rechazó la factura. ' . $detalle);
        }

        $codigoRecepcion = data_get($result, 'RespuestaServicioFacturacion.codigoRecepcion')
            ?? data_get($result, 'codigoRecepcion');

        $envio->update([
            'codigo_recepcion' => $codigoRecepcion,
            'estado'           => 'validado',
            'ultimo_mensaje'   => null,
        ]);

        $sale->codigoRecepcion = $codigoRecepcion;
        $sale->siatEnviado     = true;
        $sale->save();
    }

    // ─────────────────────────── XML ───────────────────────────

    /**
     * Devuelve la ruta del XML de la factura y, si el archivo ya no está
     * (storage limpiado, factura emitida desde otro equipo o venta que nunca
     * llegó a enviarse), lo vuelve a generar con los datos guardados en la
     * venta. Con $regenerar lo reescribe aunque exista.
     * Retorna la ruta absoluta del XML.
     */
    public function asegurarXmlFactura(Sales $sale, bool $regenerar = false): string
    {
        $xmlPath = storage_path("app/siat/sales/{$sale->id}.xml");

        if (!$regenerar && file_exists($xmlPath)) {
            return $xmlPath;
        }

        $sale->loadMissing(['details.product', 'client', 'user.agencia', 'agencia']);

        $cufd = $sale->cufd_id
            ? Cufd::find($sale->cufd_id)
            : Cufd::where('codigo', $sale->cufd)->latest('id')->first();

        if (!$cufd) {
            throw new \RuntimeException('No existe CUFD asociado a la venta para regenerar el XML');
        }

        $codigoSucursal   = (int) ($sale->codigoSucursal   ?? 0);
        $codigoPuntoVenta = (int) ($sale->codigoPuntoVenta ?? 0);
        $numeroFactura    = (int) $sale->numeroFactura;
        $leyenda          = $sale->leyenda ?: $this->leyendaSiat();
        $cuf              = (string) $sale->cuf;

        // `fechaEmision` se guarda sin milisegundos, pero el XML debe declarar
        // exactamente la fecha con la que se calculó el CUF. Se recupera del
        // propio CUF, que la lleva codificada.
        $fechaSiat = $cuf !== '' && $numeroFactura > 0
            ? $this->fechaSiatDesdeCuf($cuf, (string) $cufd->codigoControl)
            : null;

        if (!$fechaSiat) {
            // Sin CUF utilizable: se emite el documento ahora, fuera de línea,
            // con el CUFD que ya tiene asignado la venta.
            $fechaEmision  = Carbon::now('America/La_Paz');
            $mili          = str_pad((string) ((int) floor(((int) $fechaEmision->format('u')) / 1000)), 3, '0', STR_PAD_LEFT);
            $fechaSiat     = $fechaEmision->format('Y-m-d\TH:i:s') . '.' . $mili;
            $numeroFactura = $numeroFactura > 0 ? $numeroFactura : ((int) Sales::max('numeroFactura') + 1);

            $cuf = $this->generarCuf(
                (string) config('siat.nit'),
                $fechaEmision->format('YmdHis') . $mili,
                (string) $codigoSucursal,
                (string) config('siat.codigo_modalidad'),
                '1', '1', '1',
                (string) $numeroFactura,
                (string) $codigoPuntoVenta,
                (string) $cufd->codigoControl
            );

            $sale->numeroFactura         = $numeroFactura;
            $sale->venta                 = 'F';
            $sale->cuf                   = $cuf;
            $sale->cufd                  = $cufd->codigo;
            $sale->codigoSucursal        = $codigoSucursal;
            $sale->codigoPuntoVenta      = $codigoPuntoVenta;
            $sale->codigoDocumentoSector = 1;
            $sale->fechaEnvioFactura     = $fechaEmision->format('Y-m-d H:i:s');
            $sale->cufd_id               = $cufd->id;
        }

        $sale->leyenda = $leyenda;
        $sale->save();

        $xml = $this->buildSiatXml(
            $sale, $numeroFactura, $cuf,
            (string) $cufd->codigo,
            $codigoSucursal, $codigoPuntoVenta,
            $fechaSiat, $leyenda
        );

        $this->validateSiatXml($xml);
        $this->storeSiatXml($sale->id, $xml, gzencode($xml, 9));

        if (!file_exists($xmlPath)) {
            throw new \RuntimeException('No se pudo regenerar el XML de la factura para empaquetar');
        }

        error_log("SIAT paquete [{$sale->id}] XML regenerado en {$xmlPath}");

        return $xmlPath;
    }

    /**
     * Extrae la fecha de emisión (con milisegundos) codificada dentro del CUF.
     * El CUF es base16(cadena) + codigoControl, y la cadena son 54 dígitos:
     * nit(13) + fechaHora(14) + milisegundos(3) + sucursal(4) + modalidad(1) +
     * tipoEmision(1) + documentoFiscal(1) + sector(2) + factura(10) +
     * puntoVenta(4) + dígito verificador(1). Devuelve null si no se puede leer.
     */
    private function fechaSiatDesdeCuf(string $cuf, string $codigoControl): ?string
    {
        if ($codigoControl === '' || !str_ends_with($cuf, $codigoControl)) {
            return null;
        }

        $hex = strtoupper(substr($cuf, 0, strlen($cuf) - strlen($codigoControl)));

        if ($hex === '' || !ctype_xdigit($hex)) {
            return null;
        }

        $decimal = '0';
        foreach (str_split($hex) as $char) {
            $decimal = bcadd(bcmul($decimal, '16'), (string) hexdec($char));
        }

        $cadena = str_pad($decimal, 54, '0', STR_PAD_LEFT);

        if (strlen($cadena) !== 54) {
            return null;
        }

        $fechaHora = substr($cadena, 13, 14);
        $mili      = substr($cadena, 27, 3);

        try {
            $fecha = Carbon::createFromFormat('YmdHis', $fechaHora, 'America/La_Paz');
        } catch (\Throwable $e) {
            return null;
        }

        if (!$fecha || $fecha->format('YmdHis') !== $fechaHora) {
            return null;
        }

        return $fecha->format('Y-m-d\TH:i:s') . '.' . $mili;
    }

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
        <unidadMedida>58</unidadMedida>
        <precioUnitario>{$precioUnitario}</precioUnitario>
        <montoDescuento>0</montoDescuento>
        <subTotal>{$subTotal}</subTotal>
        <numeroSerie xsi:nil="true"/>
        <numeroImei xsi:nil="true"/>
    </detalle>\n
XML;
        }

        // La dirección y el teléfono son los de la sucursal que emite; si la
        // agencia no los tiene cargados se cae a los de casa matriz (.env).
        $agencia = $sale->agencia;

        $razon             = $this->xmlValue((string) env('RAZON', 'Santidad Divina'));
        $nit               = $this->xmlValue((string) config('siat.nit'));
        $municipio         = $this->xmlValue((string) env('MUNICIPIO', 'Oruro'));
        $telefono          = $this->xmlValue((string) ($agencia?->telefono ?: env('TELEFONO', '')));
        $direccion         = $this->xmlValue((string) ($agencia?->direccion ?: env('DIRECCION', '')));
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

    /**
     * Ubica el XSD oficial de SIAT. El esquema se distribuye con la app
     * (resources/siat) para no depender de la carpeta hermana siat/, que no
     * existe en todos los entornos; se puede sobreescribir con SIAT_XSD_PATH.
     */
    private function rutaXsdFactura(): ?string
    {
        $candidatos = [
            config('siat.xsd_path'),
            resource_path('siat/facturaComputarizadaCompraVenta.xsd'),
            base_path('../siat/facturaComputarizadaCompraVenta.xsd'),
        ];

        foreach ($candidatos as $candidato) {
            if (!$candidato) {
                continue;
            }
            $ruta = realpath($candidato);
            if ($ruta && is_file($ruta)) {
                return $ruta;
            }
        }

        return null;
    }

    private function validateSiatXml(string $xml): void
    {
        $schemaPath = $this->rutaXsdFactura();
        if (!$schemaPath) {
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

    /**
     * Todas las ventas se declaran a SIAT como efectivo (1), sea cual sea el
     * método de pago real. Los códigos 2/3/16 exigen datos adicionales
     * (numeroTarjeta y similares) y SIAT rechaza el paquete con el error 1012.
     */
    private function codigoMetodoPago(?string $metodoPago): int
    {
        return 1;
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
