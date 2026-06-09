<?php

declare(strict_types=1);

date_default_timezone_set('America/La_Paz');

/**
 * Configuracion SIAT centralizada.
 * Ajusta aqui CUIS/CUFD por punto de venta (0 y 1).
 */
function obtenerDatosSiat(int $codigoPuntoVenta): array
{
    $config = [
        'nit' => '681781020',
        'token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJjaGFtYmlhamh1YWNob2hpYmVyQGdtYWlsLmNvbSIsImNvZGlnb1Npc3RlbWEiOiIyMjczQzAzRkIxMjI4MDk0MDlFQkUiLCJuaXQiOiJINHNJQUFBQUFBQUFBRE96TURTM01EUXdNZ0FBQTVGbHpBa0FBQUE9IiwiaWQiOjUyMDYwODEsImV4cCI6MTgxMTg4MDkzNiwiaWF0IjoxNzgwMzU5MzA2LCJuaXREZWxlZ2FkbyI6NjgxNzgxMDIwLCJzdWJzaXN0ZW1hIjoiU0ZFIn0.5dT5uqGzU04Mj9-PQzUypzdmm63MlPua2_0MVVg9LIZgbWohCJ7KCcWcfBhiCSCUbvBj2nglnEyYTGALWtlEXQ',
        'codigoAmbiente' => 1,
        'codigoSistema' => '2273C03FB122809409EBE',
        'codigoSucursal' => 1,
        'codigoModalidad' => 2,
        'puntosVenta' => [
            0 => [
                'cuis' => '3D6DAE4A',
                'cufd' => 'FBQkFDPFI/QUE=IyODA5NDA5RUJFQz5+cFVVQ0dhVUMjI3M0MwM0ZCMT',
                'codigoControl' => '30746591D8EAF74',
            ],
            1 => [
                'cuis' => 'BE15BC8E',
                'cufd' => 'JBQUFDPFI/QUE=IyODA5NDA5RUJFQ2U+RHFUQ0dhVUMjI3M0MwM0ZCMT',
                'codigoControl' => '6B9ACD11D8EAF74',
            ],
        ],
    ];

    if (!isset($config['puntosVenta'][$codigoPuntoVenta])) {
        throw new InvalidArgumentException('Punto de venta no configurado: ' . $codigoPuntoVenta);
    }

    $puntoVenta = $config['puntosVenta'][$codigoPuntoVenta];

    return [
        'nit' => $config['nit'],
        'token' => $config['token'],
        'codigoAmbiente' => $config['codigoAmbiente'],
        'codigoSistema' => $config['codigoSistema'],
        'codigoSucursal' => $config['codigoSucursal'],
        'codigoModalidad' => $config['codigoModalidad'],
        'codigoPuntoVenta' => $codigoPuntoVenta,
        'cuis' => $puntoVenta['cuis'],
        'cufd' => $puntoVenta['cufd'],
        'codigoControl' => $puntoVenta['codigoControl'],
    ];
}
