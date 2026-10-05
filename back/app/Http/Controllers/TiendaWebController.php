<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\TiendaWebService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Tienda pública renderizada en servidor (Blade) para SEO.
 * El listado de productos reutiliza TiendaController@productos (misma lógica que /api/productos).
 */
class TiendaWebController extends Controller
{
    public function __construct(
        private readonly TiendaWebService $tienda,
        private readonly TiendaController $api,
    ) {
    }

    public function home()
    {
        $sucursales = $this->tienda->sucursales();

        $descuentos = $this->listar(['ofertas' => 1, 'orden' => 'descuento', 'per_page' => 24], $sucursales)
            ->filter(fn ($p) => $p->descuento)
            ->values();

        return view('tienda.home', [
            'sucursales'  => $sucursales,
            'categorias'  => $this->tienda->categorias(),
            'banners'     => $this->tienda->carrusel('Normal'),
            'medios'      => $this->tienda->carrusel('Medio'),
            'marcas'      => $this->tienda->carrusel('Mini'),
            'descuentos'  => $descuentos,
            'masVendidos' => $this->tienda->masVendidos(),
            'labs'        => Cache::remember('tienda_web_labs', now()->addHour(), fn () =>
                Product::whereNotNull('distribuidora')->where('distribuidora', '!=', '')
                    ->distinct()->orderBy('distribuidora')->pluck('distribuidora')->all()
            ),
        ]);
    }

    public function buscar(Request $request, ?int $categoriaId = null, bool $soloDescuentos = false)
    {
        $sucursales = $this->tienda->sucursales();
        $categorias = $this->tienda->categorias();

        $f = [
            'q'         => trim((string) $request->get('q', '')),
            'categoria' => $categoriaId ?? (int) $request->get('categoria', 0),
            'sucursal'  => (int) $request->get('sucursal', 0),
            'orden'     => in_array($request->get('orden'), ['menor', 'mayor', 'descuento'], true) ? $request->get('orden') : '',
            'descuento' => $soloDescuentos || $request->boolean('descuento'),
        ];

        $paginador = $this->api->productos(new Request([
            'search'         => $f['q'],
            'category_id'    => $f['categoria'],
            'disponible_en'  => $f['sucursal'],
            'ofertas'        => $f['descuento'] ? 1 : 0,
            'orden'          => $f['orden'],
            'per_page'       => 24,
        ]));

        $productos = $paginador->getCollection()->map(fn ($p) => $this->tienda->presentar($p, $sucursales));
        if ($f['descuento']) {
            $productos = $productos->filter(fn ($p) => $p->descuento)->values();
        }

        $categoria = $categorias->firstWhere('id', $f['categoria']);

        $titulo = match (true) {
            $f['q'] !== ''    => 'Resultados para “' . $f['q'] . '”',
            $f['descuento']   => 'Productos con descuento',
            (bool) $categoria => $categoria->nombre,
            default           => 'Todos los productos',
        };

        // URL base de la página para paginación y canonical
        $base = $categoria && $categoriaId ? $categoria->url : ($soloDescuentos ? route('tienda.descuentos') : route('tienda.buscar'));
        $query = array_filter([
            'q'         => $f['q'] ?: null,
            'categoria' => !$categoriaId && $f['categoria'] ? $f['categoria'] : null,
            'sucursal'  => $f['sucursal'] ?: null,
            'orden'     => $f['orden'] ?: null,
            'descuento' => !$soloDescuentos && $f['descuento'] ? 1 : null,
        ]);
        $pageUrl = fn (int $page) => $base . (($q = http_build_query($query + ($page > 1 ? ['page' => $page] : []))) ? '?' . $q : '');

        return view('tienda.buscar', [
            'sucursales' => $sucursales,
            'categorias' => $categorias,
            'categoria'  => $categoria,
            'filtros'    => $f,
            'titulo'     => $titulo,
            'productos'  => $productos,
            'total'      => $paginador->total(),
            'pagina'     => $paginador->currentPage(),
            'ultima'     => $paginador->lastPage(),
            'pageUrl'    => $pageUrl,
            'canonical'  => $pageUrl($paginador->currentPage()),
            // Búsquedas libres y filtros combinados no se indexan; categorías y descuentos sí
            'noindex'    => $f['q'] !== '' || $f['sucursal'] || $f['orden'] || $productos->isEmpty(),
        ]);
    }

    public function categoria(Request $request, int $id, ?string $slug = null)
    {
        return $this->buscar($request, $id);
    }

    public function descuentos(Request $request)
    {
        return $this->buscar($request, null, true);
    }

    public function producto(int $id, ?string $slug = null)
    {
        $producto = Product::where('activo', 'ACTIVO')->find($id);
        abort_if(!$producto, 404);

        $sucursales = $this->tienda->sucursales();
        $p = $this->tienda->presentar($producto, $sucursales);

        if ($slug !== $p->slug) {
            return redirect($p->url, 301);
        }

        $relacionados = $producto->category_id
            ? $this->listar(['category_id' => $producto->category_id, 'per_page' => 9], $sucursales)
                ->reject(fn ($r) => $r->id === $p->id)->take(8)->values()
            : collect();

        return view('tienda.producto', [
            'p'            => $p,
            'sucursales'   => $sucursales,
            'categoria'    => $this->tienda->categorias()->firstWhere('id', $producto->category_id),
            'relacionados' => $relacionados,
        ]);
    }

    public function sucursales()
    {
        return view('tienda.sucursales', ['sucursales' => $this->tienda->sucursales()]);
    }

    // Enlaces antiguos de frontTienda: /detalle-producto/{id}/{nombre}
    public function legacyProducto(int $id)
    {
        return redirect()->route('tienda.producto', [$id, 'p'], 301);
    }

    public function sitemap()
    {
        $xml = Cache::remember('tienda_web_sitemap', now()->addHours(6), function () {
            $urls = [
                [route('tienda.home'), 'daily', '1.0'],
                [route('tienda.descuentos'), 'daily', '0.9'],
                [route('tienda.sucursales'), 'monthly', '0.8'],
            ];
            foreach ($this->tienda->categorias() as $c) {
                $urls[] = [$c->url, 'weekly', '0.8'];
            }
            Product::where('activo', 'ACTIVO')->select('id', 'nombre', 'updated_at')->orderBy('id')
                ->chunk(2000, function ($chunk) use (&$urls) {
                    foreach ($chunk as $p) {
                        $urls[] = [route('tienda.producto', [$p->id, Str::slug($p->nombre) ?: 'producto']), 'weekly', '0.6', optional($p->updated_at)->toDateString()];
                    }
                });

            $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            foreach ($urls as $u) {
                $out .= '<url><loc>' . e($u[0]) . '</loc>' . (!empty($u[3]) ? '<lastmod>' . $u[3] . '</lastmod>' : '')
                    . '<changefreq>' . $u[1] . '</changefreq><priority>' . $u[2] . "</priority></url>\n";
            }

            return $out . '</urlset>';
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function listar(array $params, $sucursales)
    {
        return $this->api->productos(new Request($params))
            ->getCollection()
            ->map(fn ($p) => $this->tienda->presentar($p, $sucursales));
    }
}
