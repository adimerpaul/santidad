<?php

return [
    'token' => env('TOKEN'),
    'nit' => env('NIT'),
    'codigo_sistema' => env('CODIGO_SISTEMA'),
    'codigo_modalidad' => (int) env('MODALIDAD', 2),
    'codigo_ambiente' => (int) env('AMBIENTE', 2),
    'codigo_sucursal' => 0,
    'wsdl_codigos' => 'https://pilotosiatservicios.impuestos.gob.bo/v2/FacturacionCodigos?WSDL',
    'url_rest' => env('URL_SIAT'),
    'url_qr' => env('URL_SIAT2'),
];
