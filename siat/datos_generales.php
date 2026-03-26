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
        'token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJjaGFtYmlhamh1YWNob2hpYmVyQGdtYWlsLmNvbSIsImNvZGlnb1Npc3RlbWEiOiIyMjczQzAzRkIxMjI4MDk0MDlFQkUiLCJuaXQiOiJINHNJQUFBQUFBQUFBRE96TURTM01EUXdNZ0FBQTVGbHpBa0FBQUE9IiwiaWQiOjUyMDYwODEsImV4cCI6MTgwNDgxODgwNCwiaWF0IjoxNzczNDY5OTc0LCJuaXREZWxlZ2FkbyI6NjgxNzgxMDIwLCJzdWJzaXN0ZW1hIjoiU0ZFIn0.HI9ycwa7CKaO-osac16syfq5H51RiPVJ154uLrs_F6ipGsQ53UgLrZTNkMd0vEitWTuDdjoCbPsW-p1EjZ5uhg',
        'codigoAmbiente' => 2,
        'codigoSistema' => '2273C03FB122809409EBE',
        'codigoSucursal' => 0,
        'codigoModalidad' => 2,
        'puntosVenta' => [
            0 => [
                'cuis' => '99EE0FB9',
                'cufd' => 'FBQUFDPFI/QUE=IyODA5NDA5RUJFQ0E6RU9HYkRhVUMjI3M0MwM0ZCMT',
                'codigoControl' => '94927CE1C8AAF74',
            ],
            1 => [
                'cuis' => 'BE15BC8E',
                'cufd' => 'JBQUFDPFI/QUE=IyODA5NDA5RUJFQ2VpUlZHYkRhVUMjI3M0MwM0ZCMT',
                'codigoControl' => '5FD157F1C8AAF74',
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
