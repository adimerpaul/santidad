<?php

namespace App\Http\Controllers;

use App\Models\InternalCombo;
use App\Services\InternalComboService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InternalComboController extends Controller
{
    public function __construct(private readonly InternalComboService $combos) {}
    private function admin(Request $request): void { abort_unless((int) $request->user()?->id === 1, 403); }

    public function index(Request $request)
    {
        $this->admin($request);
        return $this->listing($request, false);
    }

    public function available(Request $request) { return $this->listing($request, true); }

    private function agency(Request $request): int
    {
        return (int) ((int) $request->user()->id === 1 ? $request->input('agencia_id', $request->user()->agencia_id) : $request->user()->agencia_id);
    }

    private function listing(Request $request, bool $available)
    {
        $data = $request->validate(['search' => 'nullable|string|max:100', 'page' => 'nullable|integer|min:1', 'agencia_id' => 'nullable|integer|exists:agencias,id', 'canal_venta' => 'nullable|in:sucursal,trae']);
        $query = InternalCombo::with('items.product');
        if (!empty($data['search'])) $query->where('nombre', 'like', '%'.$data['search'].'%');
        if ($available) {
            $query->where('activo', true)->whereIn('canal', ['ambos', $data['canal_venta'] ?? 'sucursal'])
                ->where(fn ($q) => $q->where('permanente', true)->orWhere(fn ($q) => $q->where('fecha_inicio', '<=', now())->where('fecha_fin', '>', now())));
        }
        $rows = $query->orderByDesc('id')->paginate(12);
        $rows->getCollection()->transform(fn ($combo) => $this->combos->present($combo, $this->agency($request)));
        return response()->json($rows)->header('Cache-Control', 'no-store');
    }

    public function store(Request $request) { return $this->save($request, new InternalCombo()); }
    public function update(Request $request, int $id) { return $this->save($request, InternalCombo::findOrFail($id)); }

    public function preview(Request $request)
    {
        $this->admin($request);
        $data = $this->validated($request);
        return response()->json(['items' => $this->combos->buildItems($data)])->header('Cache-Control', 'no-store');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nombre' => 'required|string|max:100', 'modo' => 'required|in:conjunto,producto,total', 'porcentaje' => 'required|numeric|min:0|max:99.99',
            'precio_objetivo' => 'nullable|required_if:modo,total|numeric|gt:0',
            'activo' => 'required|boolean', 'permanente' => 'required|boolean',
            'fecha_inicio' => 'nullable|required_if:permanente,false|date_format:Y-m-d H:i:s',
            'fecha_fin' => 'nullable|required_if:permanente,false|date_format:Y-m-d H:i:s|after:fecha_inicio',
            'version' => 'nullable|integer|min:1', 'items' => 'required|array|min:1|max:50',
            'items.*.product_id' => 'required|integer|distinct|exists:products,id', 'items.*.cantidad' => 'required|integer|min:1|max:1000',
            'items.*.precio_unitario' => 'required_if:modo,producto|numeric|gt:0',
            'items.*.reemplazable' => 'sometimes|boolean',
        ]);
    }

    private function save(Request $request, InternalCombo $combo)
    {
        $this->admin($request);
        $data = $this->validated($request);
        $items = $this->combos->buildItems($data);
        DB::transaction(function () use (&$combo, $data, $items) {
            if ($combo->exists) {
                $combo = InternalCombo::whereKey($combo->id)->lockForUpdate()->firstOrFail();
                abort_unless((int) ($data['version'] ?? 0) === $combo->version, 409, 'El combo cambió en otra sesión. Actualiza antes de editar.');
                $combo->version++;
            }
            $combo->fill(collect($data)->except(['items', 'version'])->all());
            $combo->canal = 'trae';
            if ($combo->permanente) { $combo->fecha_inicio = null; $combo->fecha_fin = null; }
            $combo->save();
            $combo->items()->delete();
            $combo->items()->createMany($items);
        });
        return response()->json($this->combos->present($combo->fresh('items.product')), $request->isMethod('post') ? 201 : 200)->header('Cache-Control', 'no-store');
    }

    public function destroy(Request $request, int $id)
    {
        $this->admin($request);
        DB::transaction(function () use ($id) { $combo = InternalCombo::whereKey($id)->lockForUpdate()->firstOrFail(); $combo->delete(); });
        return response()->noContent();
    }

    public function substitutes(Request $request, int $id)
    {
        $data = $request->validate([
            'agencia_id' => 'required|integer|exists:agencias,id',
            'search' => 'nullable|string|max:100', 'page' => 'nullable|integer|min:1',
        ]);
        $combo = InternalCombo::with('items')->findOrFail($id);
        try { $this->combos->assertAvailable($combo, 'trae'); }
        catch (\DomainException $e) { abort(422, $e->getMessage()); }
        abort_unless($combo->items->contains('reemplazable', true), 422, 'El combo no tiene un producto sustituible.');
        $originalId = (int) $combo->items->firstWhere('reemplazable', true)->product_id;
        $query = \App\Models\Product::where('activo', 'ACTIVO')->where('precio', '>', 0)
            ->whereNotIn('id', $combo->items->where('reemplazable', false)->pluck('product_id'));
        if (!empty($data['search'])) {
            $query->where(fn ($q) => $q->where('nombre', 'like', '%'.$data['search'].'%')->orWhere('barra', 'like', '%'.$data['search'].'%'));
        }
        $agency = $this->agency($request);
        $products = $query->orderBy('nombre')->orderBy('id')->paginate(20);
        $products->getCollection()->transform(function ($product) use ($agency, $originalId) {
            $pricing = app(\App\Services\TraePricingService::class)->resolve($product);
            return [
                'id' => $product->id, 'nombre' => $product->nombre, 'precio_trae' => $pricing['precio_venta'],
                'es_original' => (int) $product->id === $originalId,
                'stock' => max(0, min((int) $product->cantidad, (int) ($product->{'cantidadSucursal'.$agency} ?? 0))),
            ];
        });
        return response()->json($products)->header('Cache-Control', 'no-store');
    }

    public function load(Request $request, int $id)
    {
        $data = $request->validate(['agencia_id' => 'required|integer|exists:agencias,id', 'canal_venta' => 'required|in:sucursal,trae', 'cantidad' => 'required|integer|min:1|max:100',
            'reemplazo_product_id' => 'nullable|integer|exists:products,id']);
        return DB::transaction(function () use ($request, $id, $data) {
            $combo = InternalCombo::whereKey($id)->lockForUpdate()->with('items.product')->firstOrFail();
            try { $this->combos->assertAvailable($combo, $data['canal_venta']); } catch (\DomainException $e) { abort(422, $e->getMessage()); }
            foreach ($combo->items as $item) {
                if ($item->reemplazable && !empty($data['reemplazo_product_id']) && (int) $data['reemplazo_product_id'] !== (int) $item->product_id) continue;
                abort_unless($item->product, 422, 'Un producto del combo ya no existe. Actualiza el combo.');
                try { $this->combos->assertPriceAllowed($item->product, $item->precio_unitario); } catch (\DomainException $e) { abort(422, $e->getMessage()); }
            }
            try {
                $result = $this->combos->present($combo, $this->agency($request), $data['reemplazo_product_id'] ?? null);
            } catch (\DomainException $e) { abort(422, $e->getMessage()); }
            abort_if($result['combos_disponibles'] < $data['cantidad'], 422, 'No hay stock suficiente para cargar este combo en la sucursal.');
            return response()->json($result)->header('Cache-Control', 'no-store');
        });
    }
}
