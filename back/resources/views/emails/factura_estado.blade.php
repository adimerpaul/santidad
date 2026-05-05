<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; background: #f7f7f7; margin: 0; padding: 0; }
        .container { max-width: 560px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .header-anulacion { background: #c53030; padding: 24px 32px; text-align: center; }
        .header-reversion { background: #276749; padding: 24px 32px; text-align: center; }
        .header-anulacion h1, .header-reversion h1 { color: #fff; margin: 0; font-size: 22px; }
        .body { padding: 28px 32px; }
        .body p { line-height: 1.6; font-size: 15px; }
        .info-box { background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px 20px; margin: 16px 0; }
        .info-box table { width: 100%; border-collapse: collapse; }
        .info-box td { padding: 4px 0; font-size: 14px; }
        .info-box td:first-child { color: #666; width: 160px; }
        .info-box td:last-child { font-weight: bold; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; }
        .badge-anulacion { background: #fed7d7; color: #9b2c2c; }
        .badge-reversion { background: #c6f6d5; color: #276749; }
        .footer { background: #f0f4f8; padding: 16px 32px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
<div class="container">
    <div class="{{ $tipo === 'anulacion' ? 'header-anulacion' : 'header-reversion' }}">
        <h1>{{ env('RAZON', 'Santidad Divina') }}</h1>
    </div>
    <div class="body">
        <p>Estimado cliente,</p>

        @if($tipo === 'anulacion')
            <p><span class="badge badge-anulacion">✗ Factura Anulada</span></p>
            <p>Le informamos que la siguiente factura ha sido <strong>anulada</strong> en el sistema tributario SIAT.</p>
        @else
            <p><span class="badge badge-reversion">✓ Anulación Revertida</span></p>
            <p>Le informamos que la anulación de la siguiente factura ha sido <strong>revertida</strong>. Su factura se encuentra activa nuevamente en el sistema tributario SIAT.</p>
        @endif

        <div class="info-box">
            <table>
                <tr>
                    <td>N° Factura:</td>
                    <td>{{ $sale->numeroFactura }}</td>
                </tr>
                <tr>
                    <td>Fecha de emisión:</td>
                    <td>{{ \Carbon\Carbon::parse($sale->fechaEmision)->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <td>Monto total:</td>
                    <td>Bs {{ number_format($sale->montoTotal, 2) }}</td>
                </tr>
                @if($sale->cuf)
                <tr>
                    <td>CUF:</td>
                    <td style="font-size:11px; word-break:break-all;">{{ $sale->cuf }}</td>
                </tr>
                @endif
            </table>
        </div>

        @if($tipo === 'anulacion')
            <p>Si tiene alguna consulta sobre esta anulación, no dude en contactarnos.</p>
        @else
            <p>Puede verificar el estado de su factura en
            <a href="https://www.impuestos.gob.bo" target="_blank">www.impuestos.gob.bo</a>.</p>
        @endif

        <p>Atentamente,<br><strong>{{ env('RAZON', 'Santidad Divina') }}</strong><br>
        Tel. {{ env('TELEFONO') }}</p>
    </div>
    <div class="footer">
        {{ env('DIRECCION') }} &mdash; {{ env('MUNICIPIO', 'Oruro') }}, Bolivia
    </div>
</div>
</body>
</html>
