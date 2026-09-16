<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; background: #f7f7f7; margin: 0; padding: 0; }
        .container { max-width: 560px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .header { background: #2b6cb0; padding: 24px 32px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; }
        .body { padding: 28px 32px; }
        .body p { line-height: 1.6; font-size: 15px; }
        .footer { background: #f0f4f8; padding: 16px 32px; text-align: center; font-size: 12px; color: #666; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge-online  { background: #c6f6d5; color: #276749; }
        .badge-offline { background: #fed7d7; color: #9b2c2c; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>{{ env('RAZON', 'Santidad Divina') }}</h1>
    </div>
    <div class="body">
        <p>Estimado cliente,</p>
        <p>Gracias por su compra. Adjunto encontrará su factura en formato <strong>PDF</strong> y <strong>XML</strong>.</p>

        @if($online)
            <p><span class="badge badge-online">✓ Factura en línea</span>&nbsp;
            Su factura fue registrada exitosamente en el sistema tributario SIAT.</p>
        @else
            <p><span class="badge badge-offline">⚠ Fuera de línea</span>&nbsp;
            Su factura fue emitida fuera de línea. Puede verificar su estado en
            <a href="https://www.impuestos.gob.bo" target="_blank">www.impuestos.gob.bo</a>.</p>
        @endif

        <p>Conserve este documento como respaldo de su compra.</p>
        <p>Atentamente,<br><strong>{{ env('RAZON', 'Santidad Divina') }}</strong><br>
        Tel. {{ env('TELEFONO') }}</p>
    </div>
    <div class="footer">
        {{ env('DIRECCION') }} &mdash; {{ env('MUNICIPIO', 'Oruro') }}, Bolivia
    </div>
</div>
</body>
</html>
