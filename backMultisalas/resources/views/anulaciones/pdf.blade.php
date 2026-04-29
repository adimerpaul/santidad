@php
// Helpers de la vista
$fmtFecha = \Carbon\Carbon::parse($anulacion->fecha ?? now())->format('d-m-Y');
$cajero   = $anulacion->cajero ?? ($anulacion->user->name ?? '');
$seccion  = $anulacion->seccion ?? 'Boletería';
$autPor   = $anulacion->userAutoriza->name ?? '';
$anuPor   = $anulacion->userAnulacion->name ?? '';
// Marcar casillas: '☒' / '☐' (funciona bien en DomPDF)
$box = fn($on) => $on ? '☒' : '☐';
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Formulario de Anulación</title>
    <style>
        @page { margin: 22px 28px; }
        * { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; }
        body { font-size: 12px; color:#111; }
        .header { width:100%; }
        .header td { vertical-align: top; }
        .title { text-align:center; font-weight:bold; font-size: 18px; margin-top: 6px; }
        .line { border-bottom: 1px dotted #777; height: 14px; }
        .lbl { font-weight: bold; }
        .right { text-align:right; }
        .mt-4 { margin-top: 4px; }
        .mt-8 { margin-top: 8px; }
        .mt-12{ margin-top: 12px; }
        .row { width:100%; }
        .box { border:1px solid #333; padding:8px; min-height: 70px; }
        .signature-line { border-bottom:1px dotted #777; height: 14px; display:inline-block; min-width: 220px;}
        table.meta { width:100%; }
        table.meta td { vertical-align: top; }
        .small { font-size: 11px; }
        .logo { height: 36px; }
    </style>
</head>
<body>

<!-- Encabezado -->
<table class="header" cellspacing="0" cellpadding="0">
    <tr>
        <td>
            {{-- Si tienes logo, colócalo en public/imagenes/logo.png --}}
             <img class="logo" src="{{ public_path('imagenes/logo.png') }}" alt="Logo">
        </td>
        <td class="right small">
            <div><span class="lbl">CÓDIGO:</span> {{ $codigo }}</div>
            <div><span class="lbl">MONTO:</span> {{ $monto }}</div>
        </td>
    </tr>
</table>

<table class="meta mt-8" cellspacing="0" cellpadding="0">
    <tr>
        <td class="lbl">FECHA:&nbsp;</td>
        <td style="width:240px;"><div class="line">{{ $fmtFecha }}</div></td>
        <td></td>
    </tr>
</table>

<div class="title">FORMULARIO DE ANULACIÓN</div>

<table class="meta mt-8" cellspacing="0" cellpadding="2">
    <tr>
        <td class="lbl" style="width:180px;">NOMBRE DEL CAJERO:</td>
        <td><div class="line">{{ $cajero }}</div></td>
    </tr>
    <tr>
        <td class="lbl">SECCIÓN:</td>
        <td><div class="line">{{ $seccion }}</div></td>
    </tr>
</table>

<div class="mt-8">
    <span class="lbl">MOTIVO: </span>
    &nbsp;{{ $box($checks['cajero']) }} Error de cajero
    &nbsp;&nbsp;{{ $box($checks['cliente']) }} Error de cliente
    &nbsp;&nbsp;{{ $box($checks['sistema']) }} Error de Sistema
    &nbsp;&nbsp;{{ $box($checks['ventaDuplicada']) }} Venta Duplicada
</div>

<div class="mt-8">
    <div class="lbl">DETALLE:</div>
    <div class="box">
        {!! nl2br(e($anulacion->detalle ?? '')) !!}
    </div>
</div>

<div class="mt-12">
    <table style="width:100%;" cellspacing="0" cellpadding="2">
        <tr>
            <td class="lbl" style="width:160px;">AUTORIZADO POR:</td>
            <td><span class="signature-line">{{ $autPor }}</span></td>
            <td class="lbl right" style="width:80px;">FIRMA:</td>
            <td style="width:220px;"><span class="signature-line">&nbsp;</span></td>
        </tr>
        <tr>
            <td class="lbl" style="width:160px;">MODIFICADO POR:</td>
            <td><span class="signature-line">{{ $anuPor }}</span></td>
            <td class="lbl right" style="width:80px;">FIRMA:</td>
            <td style="width:220px;"><span class="signature-line">&nbsp;</span></td>
        </tr>
    </table>
</div>

<div class="mt-12" style="text-align:center;">
    <span class="signature-line" style="min-width:260px;"></span><br>
    <span class="small">FIRMA DE CAJERO</span>
</div>

</body>
</html>
