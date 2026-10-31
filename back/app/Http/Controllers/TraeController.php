<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sales;
use App\Services\TraePricingService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TraeController extends Controller
{
    public function __construct(private readonly TraePricingService $pricing) {}

    private function admin(Request $request): void
    {
        abort_unless((int) $request->user()?->id === 1, 403, 'Solo el administrador puede configurar TRAE.');
    }

    private function settingsRevision(object $settings): string
    {
        return hash('sha256', json_encode([
            $settings->fuente_precios ?? 'propio', (int) ($settings->fuente_agencia_id ?? 0),
            number_format((float) $settings->descuento_general, 2, '.', ''),
            number_format((float) $settings->comision_porcentaje, 2, '.', ''),
        ]));
    }

    public function index(Request $request)
    {
        $this->admin($request);
        $settings = DB::table('trae_settings')->find(1);
        if ($settings) $settings->revision = $this->settingsRevision($settings);
        return response()->json([
            'settings' => $settings,
            'rules' => DB::table('trae_discount_rules as r')
                ->leftJoin('categories as c', 'c.id', '=', 'r.category_id')
                ->leftJoin('products as p', 'p.id', '=', 'r.product_id')
                ->select('r.*', 'c.name as categoria', 'p.nombre as producto')->orderByDesc('r.id')->get()
                ->map(function ($rule) {
                    $rule->inicio_ms = empty($rule->fecha_inicio) ? null : \Carbon\Carbon::parse($rule->fecha_inicio)->getTimestamp() * 1000;
                    $rule->fin_ms = empty($rule->fecha_fin) ? null : \Carbon\Carbon::parse($rule->fecha_fin)->getTimestamp() * 1000;
                    return $rule;
                }),
            'server_time_ms' => (int) floor(microtime(true) * 1000),
        ])->header('Cache-Control', 'no-store');
    }

    public function settings(Request $request)
    {
        $this->admin($request);
        $data = $request->validate([
            'descuento_general' => 'required|numeric|min:0|max:99.99',
            'comision_porcentaje' => 'required|numeric|min:0|max:100',
            'fuente_precios' => 'sometimes|required|in:propio,web,app,physical',
            'fuente_agencia_id' => 'nullable|integer|exists:agencias,id',
            'revision' => 'nullable|string|size:64',
        ]);
        foreach (['descuento_general', 'comision_porcentaje'] as $field) $data[$field] = round((float) $data[$field], 2);
        DB::transaction(function () use ($data) {
            $current = DB::table('trae_settings')->where('id', 1)->lockForUpdate()->first();
            if (isset($data['revision'])) abort_unless(hash_equals($this->settingsRevision($current), $data['revision']), 409, 'La configuración cambió en otra sesión. Actualiza los valores antes de guardar.');
            unset($data['revision']);
            $source = $data['fuente_precios'] ?? $current->fuente_precios ?? 'propio';
            $agency = array_key_exists('fuente_agencia_id', $data) ? $data['fuente_agencia_id'] : ($current->fuente_agencia_id ?? null);
            if ($source === 'physical' && !$agency) throw ValidationException::withMessages(['fuente_agencia_id' => 'Selecciona la sucursal cuyos descuentos usará TRAE.']);
            if ($source === 'propio' && array_key_exists('fuente_precios', $data)) $data['fuente_agencia_id'] = null;
            $pricesChanged = $source !== ($current->fuente_precios ?? 'propio')
                || ($source !== 'propio' && (int) $agency !== (int) ($current->fuente_agencia_id ?? 0))
                || ($source === 'propio' && $data['descuento_general'] !== (float) $current->descuento_general);
            DB::table('trae_settings')->where('id', 1)->update($data + ['updated_at' => now()]);
            if ($pricesChanged) $this->validatePrices(Product::query());
        });
        $this->pricing->clearCache();
        return $this->index($request);
    }

    public function saveRule(Request $request)
    {
        $this->admin($request);
        $data = $request->validate([
            'category_id' => 'nullable|required_without:product_ids|integer|exists:categories,id',
            'product_ids' => 'nullable|required_without:category_id|array|min:1|max:200',
            'product_ids.*' => 'required|integer|distinct|exists:products,id',
            'porcentaje' => 'required|numeric|min:0|max:99.99',
            'activo' => 'required|boolean',
            'permanente' => 'sometimes|boolean',
            'fecha_inicio' => 'nullable|required_if:permanente,false|date_format:Y-m-d H:i:s',
            'fecha_fin' => 'nullable|required_if:permanente,false|date_format:Y-m-d H:i:s|after:fecha_inicio',
        ]);
        if (!$request->has('permanente') && ($request->has('fecha_inicio') || $request->has('fecha_fin'))) {
            throw ValidationException::withMessages(['permanente' => 'Indica si la vigencia es indefinida o por fechas.']);
        }
        // Older clients that only pause/edit a rule must retain its existing dates.
        $schedule = array_key_exists('permanente', $data) ? [
            'permanente' => (bool) $data['permanente'],
            'fecha_inicio' => $data['permanente'] ? null : $data['fecha_inicio'],
            'fecha_fin' => $data['permanente'] ? null : $data['fecha_fin'],
        ] : [];
        $category = $data['category_id'] ?? null;
        if ($category && !empty($data['product_ids'])) {
            throw ValidationException::withMessages(['alcance' => 'Selecciona una categoría o productos, no ambos.']);
        }
        $query = Product::query();
        $category ? $query->where('category_id', $category) : $query->whereIn('id', $data['product_ids']);
        DB::transaction(function () use ($data, $category, $query, $schedule) {
            // Same lock as checkout: one sale uses one coherent set of rules.
            DB::table('trae_settings')->where('id', 1)->lockForUpdate()->first();
            foreach ($category ? [$category] : $data['product_ids'] as $id) {
                $key = $category ? ['category_id' => $id, 'product_id' => null] : ['product_id' => $id, 'category_id' => null];
                $existing = DB::table('trae_discount_rules')->where($key)->first();
                $values = ['porcentaje' => round((float) $data['porcentaje'], 2), 'activo' => $data['activo'], 'updated_at' => now()] + $schedule;
                if ($existing) {
                    DB::table('trae_discount_rules')->where('id', $existing->id)->update($values);
                } else {
                    DB::table('trae_discount_rules')->insert($key + $values + ['created_at' => now()]);
                }
            }
            $this->validatePrices($query);
        });
        $this->pricing->clearCache();
        return $this->index($request);
    }

    public function destroy(Request $request, int $id)
    {
        $this->admin($request);
        DB::transaction(function () use ($id) {
            DB::table('trae_settings')->where('id', 1)->lockForUpdate()->first();
            $rule = DB::table('trae_discount_rules')->find($id);
            abort_unless($rule, 404);
            DB::table('trae_discount_rules')->where('id', $id)->delete();
            $query = Product::query();
            $rule->product_id ? $query->where('id', $rule->product_id) : $query->where('category_id', $rule->category_id);
            $this->validatePrices($query);
        });
        $this->pricing->clearCache();
        return response()->noContent();
    }

    public function products(Request $request)
    {
        $this->admin($request);
        $data = $request->validate([
            'search' => 'nullable|string|max:150', 'category_id' => 'nullable|integer|exists:categories,id',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:60',
        ]);
        $query = Product::query()->select('id', 'nombre', 'barra', 'precio', 'porcentaje', 'category_id', 'subcategory_id')
            ->with('category:id,name')->where('activo', 'ACTIVO');
        if (!empty($data['search'])) {
            $search = '%'.str_replace(' ', '%', trim($data['search'])).'%';
            $query->where(fn ($q) => $q->where('nombre', 'like', $search)->orWhere('barra', 'like', $search));
        }
        if (!empty($data['category_id'])) {
            $query->where('category_id', $data['category_id']);
        }
        $products = $query->orderBy('nombre')->orderBy('id')->paginate($data['per_page'] ?? 20);
        $products->getCollection()->transform(function ($product) {
            return [
                'id' => $product->id, 'nombre' => $product->nombre, 'categoria' => $product->category?->name,
                'precio' => $product->precio,
                'barra' => $product->barra,
            ] + $this->pricing->resolve($product);
        });
        return response()->json($products)->header('Cache-Control', 'no-store');
    }

    public function sales(Request $request)
    {
        $this->admin($request);
        $data = $request->validate([
            'desde' => 'nullable|date_format:Y-m-d',
            'hasta' => ['nullable', 'date_format:Y-m-d', ...($request->filled('desde') ? ['after_or_equal:desde'] : [])],
            'agencia_id' => 'nullable|integer|exists:agencias,id',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
        $query = Sales::query()->where('canal_venta', 'trae')->where('estado', '!=', 'ANULADO');
        if (!empty($data['desde'])) {
            $query->where('fechaEmision', '>=', $data['desde'].' 00:00:00');
        }
        if (!empty($data['hasta'])) {
            // Exclusive next midnight includes the entire selected last day.
            $query->where('fechaEmision', '<', \Carbon\Carbon::parse($data['hasta'])->addDay()->format('Y-m-d').' 00:00:00');
        }
        if (!empty($data['agencia_id'])) {
            $query->where('agencia_id', $data['agencia_id']);
        }
        $totals = (clone $query)->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(montoTotal), 0) as ventas, COALESCE(SUM(comision_trae_monto), 0) as comision')->first();
        $gross = Money::roundToCents($totals->ventas);
        $commission = Money::roundToCents($totals->comision);
        // The summary already counted these rows; avoid a second full count for pagination.
        $sales = $query->select('id', 'numeroFactura', 'fechaEmision', 'agencia_id', 'metodoPago', 'montoTotal', 'comision_trae_porcentaje', 'comision_trae_monto')
            ->with('agencia:id,nombre')->orderByDesc('fechaEmision')->orderByDesc('id')
            ->paginate($data['per_page'] ?? 20, ['*'], 'page', null, (int) $totals->cantidad);
        $sales->getCollection()->transform(function ($sale) {
            $sale->setAttribute('neto', Money::roundToCents((float) $sale->montoTotal - (float) $sale->comision_trae_monto));
            return $sale;
        });
        return response()->json([
            'resumen' => ['cantidad' => (int) $totals->cantidad, 'ventas' => $gross, 'comision' => $commission, 'neto' => Money::roundToCents($gross - $commission)],
            'ventas' => $sales,
        ])->header('Cache-Control', 'no-store');
    }

    private function validatePrices($query): void
    {
        // Validate the resulting rules, including exceptions and inheritance on delete/pause.
        // Called inside the transaction so invalid changes roll back as a whole.
        $this->pricing->clearCache();
        $query->where('activo', 'ACTIVO')->where('precio', '>', 0)
            ->select('id', 'precio', 'porcentaje', 'category_id', 'subcategory_id')->chunkById(200, function ($products) {
                foreach ($products as $product) {
                    foreach ($this->pricing->validationInstants($product) as $at) {
                        $price = $this->pricing->resolve($product, $at);
                        if ($price['porcentaje'] > 0 && $price['precio_venta'] <= 0) {
                            throw ValidationException::withMessages(['porcentaje' => 'El descuento o su vencimiento dejaría al menos un producto con precio Bs 0,00.']);
                        }
                    }
                }
            });
    }
}
