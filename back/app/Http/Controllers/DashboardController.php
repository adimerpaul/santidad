<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sales;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Resumen de ventas para el dashboard: totales, evolución en el tiempo,
 * productos más vendidos, usuarios que más venden y desglose por agencia.
 * Todo se calcula en la base de datos (no se traen las ventas al PHP) para
 * que un rango de varios meses siga respondiendo rápido.
 */
class DashboardController extends Controller
{
    /** Monto real de la venta: el total menos el descuento aplicado. */
    private const NETO = 'sales.montoTotal - COALESCE(sales.descuento, 0)';

    public function resumen(Request $request)
    {
        [$desde, $hasta] = $this->rango($request);

        $agenciaId = $this->agenciaFiltro($request);
        $userId    = (int) $request->get('user_id') ?: null;

        // Un rango largo recorre cientos de miles de ventas y su millón largo de
        // detalles: se cachea el resultado para que volver a abrir el dashboard
        // sea inmediato. El botón "Actualizar" manda refrescar=1 y lo salta.
        $clave = 'dashboard:' . md5(implode('|', [
            $desde->format('Y-m-d H:i'), $hasta->format('Y-m-d H:i'), $agenciaId, $userId,
        ]));
        $minutos = $desde->diffInDays($hasta) > 62 ? 30 : 2;

        if (!$request->boolean('refrescar') && Cache::has($clave)) {
            return response()->json(Cache::get($clave));
        }

        $datos = $this->construirResumen($request, $desde, $hasta, $agenciaId, $userId);
        Cache::put($clave, $datos, now()->addMinutes($minutos));

        return response()->json($datos);
    }

    private function construirResumen(Request $request, Carbon $desde, Carbon $hasta, ?int $agenciaId, ?int $userId): array
    {
        // Periodo anterior del mismo tamaño, para las variaciones porcentuales.
        $dias           = max($desde->diffInDays($hasta) + 1, 1);
        $desdeAnterior  = $desde->copy()->subDays($dias);
        $hastaAnterior  = $desde->copy()->subSecond();

        $productos = $this->productosVendidos($desde, $hasta, $agenciaId, $userId);

        $actual = $this->totales($desde, $hasta, $agenciaId, $userId, $productos);
        // Del periodo anterior solo se necesitan los importes para las
        // variaciones: recorrer sus detalles costaría segundos y no se muestra.
        $anterior = $this->totales($desdeAnterior, $hastaAnterior, $agenciaId, $userId);

        $granularidad = $this->granularidad($desde, $hasta);

        return [
            'rango' => [
                'desde'        => $desde->format('Y-m-d H:i:s'),
                'hasta'        => $hasta->format('Y-m-d H:i:s'),
                'dias'         => $dias,
                'granularidad' => $granularidad,
            ],
            'resumen' => array_merge($actual, [
                'variacionIngresos' => $this->variacion($actual['ingresos'], $anterior['ingresos']),
                'variacionGanancia' => $this->variacion($actual['ganancia'], $anterior['ganancia']),
                'variacionVentas'   => $this->variacion($actual['ventas'], $anterior['ventas']),
                'anterior'          => $anterior,
            ]),
            'serie'        => $this->serie($desde, $hasta, $agenciaId, $userId, $granularidad),
            'topProductos' => $productos['ranking'],
            'topUsuarios'  => $this->topUsuarios($desde, $hasta, $agenciaId, $userId),
            // La comparativa muestra todas las agencias (así se ve el ranking
            // aunque se esté filtrando una), salvo para usuarios no admin.
            'porAgencia'   => $this->porAgencia($desde, $hasta, $userId, $this->agenciaForzada($request)),
            'metodosPago'  => $this->metodosPago($desde, $hasta, $agenciaId, $userId),
            'generado'     => now()->format('Y-m-d H:i:s'),
        ];
    }

    // ─────────────────────────── Filtros ───────────────────────────

    /**
     * Acepta 'desde'/'hasta' (fecha o fecha y hora) o un 'periodo' con nombre
     * (hoy, ayer, semana, mes, anio). Sin nada, devuelve el día de hoy.
     */
    private function rango(Request $request): array
    {
        $desde = $request->get('desde');
        $hasta = $request->get('hasta');

        if ($desde && $hasta) {
            return [
                Carbon::parse($desde)->startOfDay(),
                $this->finDeRango($hasta),
            ];
        }

        $hoy = Carbon::now();

        return match ($request->get('periodo')) {
            'ayer'   => [$hoy->copy()->subDay()->startOfDay(), $hoy->copy()->subDay()->endOfDay()],
            'semana' => [$hoy->copy()->subDays(6)->startOfDay(), $hoy->copy()->endOfDay()],
            'mes'    => [$hoy->copy()->subDays(29)->startOfDay(), $hoy->copy()->endOfDay()],
            'anio'   => [$hoy->copy()->subMonths(11)->startOfMonth(), $hoy->copy()->endOfDay()],
            default  => [$hoy->copy()->startOfDay(), $hoy->copy()->endOfDay()],
        };
    }

    /**
     * Si viene solo la fecha se toma hasta el final del día: con la hora en 00:00
     * el último día del rango quedaría fuera.
     */
    private function finDeRango(string $hasta): Carbon
    {
        $fecha = Carbon::parse($hasta);

        return $fecha->format('H:i:s') === '00:00:00' ? $fecha->endOfDay() : $fecha;
    }

    /**
     * Solo el administrador ve todas las agencias; el resto queda limitado a la
     * suya aunque el navegador mande otro id.
     */
    private function agenciaFiltro(Request $request): ?int
    {
        return $this->agenciaForzada($request) ?: ((int) $request->get('agencia_id') ?: null);
    }

    /** Agencia a la que está atado el usuario, o null si es el administrador. */
    private function agenciaForzada(Request $request): ?int
    {
        $usuario = $request->user();

        return $usuario && (string) $usuario->id !== '1' && $usuario->agencia_id
            ? (int) $usuario->agencia_id
            : null;
    }

    private function base(Carbon $desde, Carbon $hasta, ?int $agenciaId, ?int $userId)
    {
        $query = Sales::query()
            ->whereBetween('sales.fechaEmision', [$desde, $hasta])
            ->where('sales.estado', '!=', 'ANULADO');

        if ($agenciaId) {
            $query->where('sales.agencia_id', $agenciaId);
        }

        if ($userId) {
            $query->where('sales.user_id', $userId);
        }

        return $query;
    }

    /** Misma base pero sobre la tabla `details`, para los productos vendidos. */
    private function baseDetalles(Carbon $desde, Carbon $hasta, ?int $agenciaId, ?int $userId)
    {
        $query = DB::table('details as d')
            ->join('sales as s', 's.id', '=', 'd.sale_id')
            ->whereNull('s.deleted_at')
            ->whereBetween('s.fechaEmision', [$desde, $hasta])
            ->where('s.estado', '!=', 'ANULADO')
            ->where('s.tipoVenta', 'Ingreso');

        if ($agenciaId) {
            $query->where('s.agencia_id', $agenciaId);
        }

        if ($userId) {
            $query->where('s.user_id', $userId);
        }

        return $query;
    }

    // ─────────────────────────── Bloques ───────────────────────────

    private function totales(Carbon $desde, Carbon $hasta, ?int $agenciaId, ?int $userId, ?array $productos = null): array
    {
        $neto = self::NETO;

        $row = $this->base($desde, $hasta, $agenciaId, $userId)
            ->selectRaw("
                SUM(CASE WHEN sales.tipoVenta = 'Ingreso' THEN {$neto} ELSE 0 END) as ingresos,
                SUM(CASE WHEN sales.tipoVenta = 'Egreso'  THEN {$neto} ELSE 0 END) as egresos,
                SUM(CASE WHEN sales.tipoVenta = 'Ingreso' THEN 1 ELSE 0 END)      as ventas,
                SUM(COALESCE(sales.descuento, 0))                                 as descuentos,
                SUM(CASE WHEN sales.venta = 'F' THEN 1 ELSE 0 END)                as facturas,
                SUM(CASE WHEN sales.venta = 'F' AND sales.siatEnviado = 0 THEN 1 ELSE 0 END) as pendientesSiat
            ")
            ->first();

        $ingresos = round((float) ($row->ingresos ?? 0), 2);
        $egresos  = round((float) ($row->egresos ?? 0), 2);
        $ventas   = (int) ($row->ventas ?? 0);

        $costo         = round((float) ($productos['costo'] ?? 0), 2);
        $ventaConCosto = round((float) ($productos['ventaConCosto'] ?? 0), 2);
        $margen        = round($ventaConCosto - $costo, 2);

        return [
            'ingresos'         => $ingresos,
            'egresos'          => $egresos,
            // Misma definición que el panel de movimientos: ingresos - egresos.
            'ganancia'         => round($ingresos - $egresos, 2),
            'ventas'           => $ventas,
            'ticketPromedio'   => $ventas > 0 ? round($ingresos / $ventas, 2) : 0,
            'descuentos'       => round((float) ($row->descuentos ?? 0), 2),
            'facturas'         => (int) ($row->facturas ?? 0),
            'pendientesSiat'   => (int) ($row->pendientesSiat ?? 0),
            'unidades'         => round((float) ($productos['unidades'] ?? 0), 2),
            'productosDistintos' => (int) ($productos['distintos'] ?? 0),
            'costoEstimado'    => $costo,
            'margenEstimado'   => $margen,
            'margenPorcentaje' => $ventaConCosto > 0 ? round(($margen / $ventaConCosto) * 100, 1) : 0,
        ];
    }

    /**
     * Un solo día se ve por horas, hasta dos meses por día y más allá por mes:
     * un gráfico diario de un año son 365 barras ilegibles.
     */
    private function granularidad(Carbon $desde, Carbon $hasta): string
    {
        $dias = $desde->diffInDays($hasta) + 1;

        return $dias <= 1 ? 'hora' : ($dias <= 62 ? 'dia' : 'mes');
    }

    private function serie(Carbon $desde, Carbon $hasta, ?int $agenciaId, ?int $userId, string $granularidad): array
    {
        $neto    = self::NETO;
        $formato = match ($granularidad) {
            'hora'  => '%Y-%m-%d %H:00:00',
            'mes'   => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $filas = $this->base($desde, $hasta, $agenciaId, $userId)
            ->selectRaw("
                DATE_FORMAT(sales.fechaEmision, '{$formato}') as periodo,
                SUM(CASE WHEN sales.tipoVenta = 'Ingreso' THEN {$neto} ELSE 0 END) as ingresos,
                SUM(CASE WHEN sales.tipoVenta = 'Egreso'  THEN {$neto} ELSE 0 END) as egresos,
                SUM(CASE WHEN sales.tipoVenta = 'Ingreso' THEN 1 ELSE 0 END)      as ventas
            ")
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get()
            ->keyBy('periodo');

        // Se recorre el rango completo para que los periodos sin ventas salgan
        // en cero y el gráfico no invente una línea continua donde no la hubo.
        $serie  = [];
        $cursor = $granularidad === 'mes' ? $desde->copy()->startOfMonth() : $desde->copy();

        while ($cursor <= $hasta) {
            $clave = match ($granularidad) {
                'hora'  => $cursor->format('Y-m-d H:00:00'),
                'mes'   => $cursor->format('Y-m'),
                default => $cursor->format('Y-m-d'),
            };

            $fila     = $filas->get($clave);
            $ingresos = round((float) ($fila->ingresos ?? 0), 2);
            $egresos  = round((float) ($fila->egresos ?? 0), 2);

            $serie[] = [
                'periodo'  => $clave,
                'etiqueta' => match ($granularidad) {
                    'hora'  => $cursor->format('H:i'),
                    'mes'   => $cursor->locale('es')->isoFormat('MMM YY'),
                    default => $cursor->format('d/m'),
                },
                'ingresos' => $ingresos,
                'egresos'  => $egresos,
                'ganancia' => round($ingresos - $egresos, 2),
                'ventas'   => (int) ($fila->ventas ?? 0),
            ];

            match ($granularidad) {
                'hora'  => $cursor->addHour(),
                'mes'   => $cursor->addMonth(),
                default => $cursor->addDay(),
            };
        }

        return $serie;
    }

    /**
     * Agrupa las líneas vendidas por producto una sola vez: de aquí salen tanto
     * los totales (unidades, costo, margen) como el ranking de más vendidos.
     * Unir esto al catálogo dentro del SQL multiplicaba por tres el tiempo de
     * la consulta, así que los nombres y costos se cruzan después en PHP.
     */
    private function productosVendidos(Carbon $desde, Carbon $hasta, ?int $agenciaId, ?int $userId): array
    {
        $filas = $this->baseDetalles($desde, $hasta, $agenciaId, $userId)
            ->selectRaw('d.product_id, SUM(d.cantidad) as cantidad, SUM(d.subTotal) as total')
            ->groupBy('d.product_id')
            ->get();

        $catalogo = Product::whereIn('id', $filas->pluck('product_id')->filter()->all())
            ->select('id', 'nombre', 'costo')
            ->get()
            ->keyBy('id');

        $unidades = 0.0;
        $costo    = 0.0;
        $conCosto = 0.0;
        $ranking  = [];

        foreach ($filas as $fila) {
            $cantidad  = (float) $fila->cantidad;
            $total     = (float) $fila->total;
            $producto  = $catalogo->get($fila->product_id);
            $costoUnit = (float) ($producto->costo ?? 0);

            $unidades += $cantidad;

            // Solo se estima margen donde hay costo cargado; si no, la venta
            // queda fuera del cálculo para no inflar el porcentaje.
            if ($costoUnit > 0) {
                $costo    += $cantidad * $costoUnit;
                $conCosto += $total;
            }

            $ranking[] = [
                'id'       => (int) $fila->product_id,
                'nombre'   => $producto->nombre ?? 'SIN NOMBRE',
                'cantidad' => round($cantidad, 2),
                'total'    => round($total, 2),
                'margen'   => $costoUnit > 0 ? round($total - ($cantidad * $costoUnit), 2) : 0,
            ];
        }

        usort($ranking, fn ($a, $b) => $b['cantidad'] <=> $a['cantidad']);

        return [
            'unidades'      => $unidades,
            'distintos'     => $filas->count(),
            'costo'         => $costo,
            'ventaConCosto' => $conCosto,
            'ranking'       => array_slice($ranking, 0, 10),
        ];
    }

    private function topUsuarios(Carbon $desde, Carbon $hasta, ?int $agenciaId, ?int $userId): array
    {
        $neto = self::NETO;

        return $this->base($desde, $hasta, $agenciaId, $userId)
            ->where('sales.tipoVenta', 'Ingreso')
            ->leftJoin('users as u', 'u.id', '=', 'sales.user_id')
            ->selectRaw("
                sales.user_id,
                COALESCE(MAX(u.name), MAX(sales.usuario)) as nombre,
                COUNT(*)           as ventas,
                SUM({$neto})       as total
            ")
            ->groupBy('sales.user_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($fila) => [
                'id'      => (int) $fila->user_id,
                'nombre'  => $fila->nombre ?: 'SIN USUARIO',
                'ventas'  => (int) $fila->ventas,
                'total'   => round((float) $fila->total, 2),
            ])
            ->all();
    }

    private function porAgencia(Carbon $desde, Carbon $hasta, ?int $userId, ?int $agenciaForzada = null): array
    {
        $neto = self::NETO;

        return $this->base($desde, $hasta, $agenciaForzada, $userId)
            ->where('sales.tipoVenta', 'Ingreso')
            ->leftJoin('agencias as a', 'a.id', '=', 'sales.agencia_id')
            ->selectRaw("
                sales.agencia_id,
                MAX(a.nombre) as nombre,
                COUNT(*)      as ventas,
                SUM({$neto})  as total
            ")
            ->groupBy('sales.agencia_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($fila) => [
                'id'     => (int) $fila->agencia_id,
                'nombre' => $fila->nombre ?: 'SIN AGENCIA',
                'ventas' => (int) $fila->ventas,
                'total'  => round((float) $fila->total, 2),
            ])
            ->all();
    }

    /**
     * Las ventas 'Personalizado' se cobran en dos partes (efectivo + QR), así que
     * se reparten en sus dos montos en lugar de contarse como un método propio.
     */
    private function metodosPago(Carbon $desde, Carbon $hasta, ?int $agenciaId, ?int $userId): array
    {
        $neto = self::NETO;

        $filas = $this->base($desde, $hasta, $agenciaId, $userId)
            ->where('sales.tipoVenta', 'Ingreso')
            ->selectRaw("
                COALESCE(sales.metodoPago, 'Efectivo') as metodo,
                COUNT(*)     as ventas,
                SUM({$neto}) as total,
                SUM(COALESCE(sales.montoEfectivo, 0)) as efectivo,
                SUM(COALESCE(sales.montoQr, 0))       as qr
            ")
            ->groupBy('metodo')
            ->get();

        $totales = [];

        foreach ($filas as $fila) {
            if ($fila->metodo === 'Personalizado') {
                $this->acumular($totales, 'Efectivo', (float) $fila->efectivo, (int) $fila->ventas);
                $this->acumular($totales, 'QR', (float) $fila->qr, 0);
                continue;
            }

            $this->acumular($totales, $fila->metodo, (float) $fila->total, (int) $fila->ventas);
        }

        $resultado = array_values($totales);
        usort($resultado, fn ($a, $b) => $b['total'] <=> $a['total']);

        return array_map(
            fn ($item) => ['metodo' => $item['metodo'], 'ventas' => $item['ventas'], 'total' => round($item['total'], 2)],
            $resultado
        );
    }

    private function acumular(array &$totales, string $metodo, float $total, int $ventas): void
    {
        // En la base conviven 'Qr' y 'QR' escritos por distintas pantallas: sin
        // unificarlos el gráfico muestra el mismo método dos veces.
        $metodo = mb_strtolower(trim($metodo)) === 'qr'
            ? 'QR'
            : ucfirst(mb_strtolower(trim($metodo)));

        if (!isset($totales[$metodo])) {
            $totales[$metodo] = ['metodo' => $metodo, 'total' => 0, 'ventas' => 0];
        }

        $totales[$metodo]['total']  += $total;
        $totales[$metodo]['ventas'] += $ventas;
    }

    /** Variación porcentual contra el periodo anterior; null si no hay con qué comparar. */
    private function variacion(float $actual, float $anterior): ?float
    {
        if ($anterior == 0.0) {
            return null;
        }

        return round((($actual - $anterior) / abs($anterior)) * 100, 1);
    }
}
