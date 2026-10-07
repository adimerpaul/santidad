<?php

namespace App\Services;

use App\Models\Agencia;
use App\Models\Carousel;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Datos para la tienda pública renderizada con Blade (farmaciasantidaddivina.com).
 * Usa la misma lógica de precios/promociones que la API pública (/api/productos).
 */
class TiendaWebService
{
    public const WHATSAPP = '59172319869';

    public function __construct(private readonly PromotionPricingService $promotionPricing)
    {
    }

    /** Sucursales activas con horario interpretado y estado abierto/cerrado. */
    public function sucursales(): Collection
    {
        $agencias = Cache::remember('tienda_web_agencias', now()->addMinutes(10), fn () =>
            Agencia::where('status', 'ACTIVO')->orderBy('id')->get()->toArray()
        );

        return collect($agencias)->map(function (array $a) {
            $nombre = $this->titulo($a['nombre'] ?? '');
            $horas  = $this->parseHorario($a['horario'] ?? '');
            $abierto = $this->estaAbierta($horas);
            $lat = is_numeric($a['latitud'] ?? null) ? (float) $a['latitud'] : null;
            $lon = is_numeric($a['longitud'] ?? null) ? (float) $a['longitud'] : null;
            $tel = trim((string) ($a['telefono'] ?? ''));

            return (object) [
                'id'        => (int) $a['id'],
                'nombre'    => $nombre,
                'corto'     => $this->nombreCorto($nombre),
                'direccion' => $a['direccion'] ?: 'Oruro',
                'telefono'  => $tel,
                'telHref'   => $tel !== '' ? 'tel:+5912' . preg_replace('/\D/', '', $tel) : null,
                'whatsapp'  => preg_replace('/\D/', '', (string) ($a['whatsapp'] ?? '')),
                'horario'   => trim(($a['horario'] ?? '') . (!empty($a['atencion']) ? ' · ' . $a['atencion'] : '')),
                'h24'       => $horas['h24'],
                'abierto'   => $abierto,
                'estado'    => $abierto ? ($horas['h24'] ? 'Abierto 24 h' : 'Abierto ahora') : 'Cerrado',
                'lat'       => $lat,
                'lon'       => $lon,
                'mapa'      => $lat !== null && $lon !== null ? $this->mapaEmbed($lat, $lon) : null,
                'comoLlegar'=> $lat !== null && $lon !== null
                    ? "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lon}"
                    : ($a['gps'] ?? null),
                'facebook'  => $a['facebook'] ?? null,
            ];
        })->values();
    }

    public function categorias(): Collection
    {
        // Mismo caché que CategoryController@index
        $totales = Cache::remember('tienda_web_cat_totales', now()->addHour(), fn () =>
            Product::where('activo', 'ACTIVO')
                ->whereNotNull('category_id')
                ->selectRaw('category_id, COUNT(*) as total')
                ->groupBy('category_id')
                ->pluck('total', 'category_id')
                ->all()
        );

        return collect(Cache::rememberForever('categories_list', fn () => Category::all()))
            ->map(function ($c) use ($totales) {
                [$icono, $color] = $this->estiloCategoria($c->name);

                return (object) [
                    'id'     => $c->id,
                    'nombre' => $c->name,
                    'slug'   => Str::slug($c->name),
                    'url'    => route('tienda.categoria', [$c->id, Str::slug($c->name)]),
                    'icono'  => $icono,
                    'color'  => $color,
                    'total'  => (int) ($totales[$c->id] ?? 0),
                ];
            });
    }

    /** Carruseles por tipo: Normal (principal), Medio, Mini (marcas). */
    public function carrusel(string $tipo): Collection
    {
        return Cache::remember('tienda_web_carousel_' . $tipo, now()->addMinutes(5), fn () =>
            Carousel::where('status', 'active')->where('tipo', $tipo)->orderByDesc('id')->get()
        )->map(fn ($c) => (object) [
            'id'     => $c->id,
            // En la BD "url" suele guardar el nombre de la imagen; solo se usa si es un enlace real
            'url'    => $this->enlaceValido($c->url),
            'img'    => $this->imagenUrl($c->image),
            'movil'  => $c->imageResponsive ? $this->imagenUrl($c->imageResponsive) : null,
        ]);
    }

    /** Productos más vendidos (misma consulta que SalesController@topSellers). */
    public function masVendidos(int $dias = 30, int $limite = 16): Collection
    {
        $ids = Cache::remember("tienda_web_top_{$dias}_{$limite}", now()->addHour(), function () use ($dias, $limite) {
            return DB::table('details as d')
                ->join('sales as s', 's.id', '=', 'd.sale_id')
                ->whereBetween('s.fechaEmision', [Carbon::now()->subDays($dias - 1)->startOfDay(), Carbon::now()->endOfDay()])
                ->where('s.estado', '!=', 'ANULADO')
                ->where('s.tipoVenta', 'Ingreso')
                ->whereNotNull('d.product_id')
                ->select('d.product_id', DB::raw('SUM(d.cantidad) as total'))
                ->groupBy('d.product_id')
                ->orderByDesc('total')
                ->limit($limite * 2)
                ->pluck('d.product_id')
                ->all();
        });

        if (empty($ids)) {
            return collect();
        }

        $sucursales = $this->sucursales();
        $productos = Product::whereIn('id', $ids)->where('activo', 'ACTIVO')->get()->keyBy('id');

        return collect($ids)
            ->map(fn ($id) => $productos[$id] ?? null)
            ->filter()
            ->take($limite)
            ->map(fn ($p) => $this->presentar($p, $sucursales, true))
            ->values();
    }

    /**
     * Normaliza un Product para las vistas: precios con promoción,
     * stock por sucursal y textos de disponibilidad.
     */
    public function presentar(Product $p, ?Collection $sucursales = null, bool $topVentas = false): object
    {
        $sucursales ??= $this->sucursales();
        $pricing = $this->promotionPricing->resolve($p, 'web', null);
        $antes  = (float) $pricing['precio_original'];
        $precio = (float) $pricing['precio_venta'];
        // Porcentaje configurado en el sistema, no el recalculado desde precios redondeados
        $pct    = $antes > $precio ? (float) $pricing['porcentaje'] : 0;
        $pct    = fmod($pct, 1.0) == 0.0 ? (int) $pct : rtrim(rtrim(number_format($pct, 2, '.', ''), '0'), '.');

        $stock = [];
        $total = 0;
        foreach ($sucursales as $s) {
            $n = max(0, (int) ($p->{'cantidadSucursal' . $s->id} ?? 0));
            $stock[$s->id] = $n;
            $total += $n;
        }
        $donde = array_keys(array_filter($stock));
        $corto = fn ($id) => optional($sucursales->firstWhere('id', $id))->corto ?? 'una sucursal';

        $disponibilidad = match (true) {
            $total <= 0                               => 'Agotado',
            count($donde) === 1                       => 'Solo en ' . $corto($donde[0]),
            count($donde) === $sucursales->count()    => 'En todas las sucursales',
            default                                   => 'En ' . count($donde) . ' sucursales',
        };

        $slug = Str::slug($p->nombre) ?: 'producto';

        return (object) [
            'id'          => $p->id,
            'nombre'      => $p->nombre,
            'slug'        => $slug,
            'url'         => route('tienda.producto', [$p->id, $slug]),
            'img'         => $this->imagenUrl($p->imagen),
            'lab'         => $p->marca ?: ($p->distribuidora ?: ''),
            'categoriaId' => $p->category_id,
            'unidad'      => mb_strtolower($p->unidad ?: 'unidad'),
            'descripcion' => $p->descripcion,
            'precio'      => $precio,
            'antes'       => $antes,
            'pct'         => $pct,
            'descuento'   => $pct > 0,
            'promocion'   => $pricing['promocion_nombre'] ?? null,
            'stock'       => $stock,
            'total'       => $total,
            'agotado'     => $total <= 0,
            'disponibilidad' => $disponibilidad,
            'stockTxt'    => match (true) {
                $total <= 0  => 'Sin stock',
                $total > 100 => 'Más de 100 unidades disponibles',
                default      => $total . ($total === 1 ? ' unidad disponible' : ' unidades disponibles'),
            },
            'colorDisp'   => $total <= 0 ? 'var(--color-accent-2)' : (count($donde) === 1 ? 'var(--color-accent-2-700)' : 'var(--color-accent-700)'),
            'topVentas'   => $topVentas,
            'raw'         => $p,
        ];
    }

    /** Datos mínimos que necesita el carrito en el navegador. */
    public function datosCarrito(object $p): array
    {
        return [
            'id' => $p->id, 'name' => $p->nombre, 'img' => $p->img, 'url' => $p->url,
            'price' => $p->precio, 'old' => $p->antes, 'stock' => $p->stock, 'max' => max(1, $p->total),
        ];
    }

    public function imagenUrl(?string $img): string
    {
        $img = (string) $img;
        if (preg_match('#^https?://#i', $img)) {
            return $img;
        }
        if ($img === '' || !file_exists(public_path('images/' . $img))) {
            $img = 'productDefault.jpg';
        }

        return asset('images/' . rawurlencode($img));
    }

    public static function bs($n): string
    {
        return 'Bs ' . number_format((float) $n, 2, '.', ',');
    }

    // ───────────── helpers ─────────────

    private function enlaceValido(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '' || preg_match('/\.(jpe?g|png|gif|webp|svg)$/i', $url)) {
            return null;
        }

        return preg_match('#^(https?://|/|\#)#i', $url) ? $url : null;
    }

    private function parseHorario(string $h): array
    {
        $t = mb_strtolower($h);
        if (preg_match('/24\s*h/', $t)) {
            return ['h24' => true, 'desde' => 0, 'hasta' => 1440];
        }
        preg_match_all('/(\d{1,2})(?::(\d{2}))?\s*(am|pm|a\.m\.|p\.m\.)?/', $t, $m, PREG_SET_ORDER);
        if (count($m) < 2) {
            return ['h24' => false, 'desde' => 8 * 60, 'hasta' => 22 * 60];
        }
        $min = function ($x) {
            $hh = ((int) $x[1]) % 24;
            $ap = isset($x[3]) ? $x[3][0] ?? '' : '';
            if ($ap === 'p' && $hh < 12) $hh += 12;
            if ($ap === 'a' && $hh === 12) $hh = 0;
            return $hh * 60 + (int) ($x[2] ?? 0);
        };

        return ['h24' => false, 'desde' => $min($m[0]), 'hasta' => $min($m[1])];
    }

    private function estaAbierta(array $h): bool
    {
        if ($h['h24']) {
            return true;
        }
        $ahora = now()->hour * 60 + now()->minute;

        return $h['hasta'] > $h['desde']
            ? $ahora >= $h['desde'] && $ahora < $h['hasta']
            : $ahora >= $h['desde'] || $ahora < $h['hasta'];
    }

    private function mapaEmbed(float $lat, float $lon): string
    {
        $d = 0.008;

        return 'https://www.openstreetmap.org/export/embed.html?bbox='
            . ($lon - $d) . ',' . ($lat - $d * 0.7) . ',' . ($lon + $d) . ',' . ($lat + $d * 0.7)
            . "&layer=mapnik&marker={$lat},{$lon}";
    }

    // "FCIA SANTIDAD-DIVINA S.R.L. 1" -> "Fcia Santidad-Divina S.R.L. 1"
    private function titulo(string $s): string
    {
        $t = mb_convert_case(mb_strtolower($s), MB_CASE_TITLE, 'UTF-8');
        $t = preg_replace_callback('/S\.r\.l\.|\b(Iii|Ii|Iv)\b/u', fn ($m) => mb_strtoupper($m[0]), $t);

        return $t;
    }

    // "Santidad Divina II (Parque De La Union)" -> "Parque De La Union"; "...S.R.L. 1" -> "Sucursal 1"
    private function nombreCorto(string $nombre): string
    {
        if (preg_match('/\(([^)]+)\)/', $nombre, $m)) {
            return $m[1];
        }
        if (preg_match('/S\.R\.L\.\s*(\d+)/i', $nombre, $m)) {
            return 'Sucursal ' . $m[1];
        }

        return $nombre;
    }

    /** Ícono (Phosphor duotone) y color de acento según el nombre de la categoría. */
    private function estiloCategoria(string $nombre): array
    {
        $n = Str::lower(Str::ascii($nombre));
        $mapa = [
            '/oferta|promo|descuento/'               => ['ph-seal-percent',       '#d6006c'],
            '/market|super|abarrote/'                => ['ph-basket',             '#ea6a0c'],
            '/derm|cosm|belleza|piel/'               => ['ph-drop-half',          '#8b5cf6'],
            '/beb|mama|infant|nin/'                  => ['ph-baby',               '#f08a00'],
            '/higien|personal|dental|bucal/'         => ['ph-tooth',              '#0f9f9a'],
            '/adulto|mayor|geri/'                    => ['ph-person-simple-walk', '#4f6bed'],
            '/vitamin|suplement|mineral|natural/'    => ['ph-leaf',               '#16a34a'],
            '/sexual|intim/'                         => ['ph-heart',              '#e11d48'],
            '/insumo|material|equipo|ortop/'         => ['ph-bandaids',           '#0284c7'],
            '/medic|salud|farma/'                    => ['ph-pill',               '#1f86e6'],
        ];
        foreach ($mapa as $re => [$icono, $color]) {
            if (preg_match($re, $n)) {
                return ['ph-duotone ' . $icono, $color];
            }
        }

        return ['ph-duotone ph-first-aid-kit', '#0088b0'];
    }
}
