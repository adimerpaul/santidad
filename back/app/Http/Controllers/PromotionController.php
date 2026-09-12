<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Models\Product;
use App\Services\PromotionPricingService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PromotionController extends Controller
{
    public function __construct(private readonly PromotionPricingService $pricing)
    {
    }

    public function index(Request $request)
    {
        $this->ensureAdmin($request);

        return Promotion::query()
            ->with([
                'category:id,name',
                'products:id,nombre',
                'agencias:id,nombre',
            ])
            ->orderByDesc('activo')
            ->orderByDesc('id')
            ->get();
    }

    public function announcements(Request $request)
    {
        $request->validate(['agencia_id' => ['nullable', 'integer', 'min:1']]);
        $user = $request->user();
        $agenciaId = (int) ((int) $user->id === 1
            ? $request->input('agencia_id', $user->agencia_id)
            : $user->agencia_id);
        $now = now();
        $promotions = Promotion::query()
            ->with(['category:id,name', 'products:id,nombre'])
            ->where('activo', true)
            ->where(fn ($query) => $query->where('canal_fisico', true)
                ->orWhere('canal_web', true)->orWhere('canal_app', true))
            ->where(fn ($query) => $query->whereNull('fecha_inicio')->orWhere('fecha_inicio', '<=', $now))
            ->where(fn ($query) => $query->where('permanente', true)
                ->orWhereNull('fecha_fin')->orWhere('fecha_fin', '>=', $now))
            ->where(function ($query) use ($agenciaId) {
                $query->where('todas_agencias', true);
                if ($agenciaId) {
                    $query->orWhereHas('agencias', fn ($agency) => $agency->where('agencias.id', $agenciaId));
                }
            })
            ->orderByDesc('porcentaje')->orderByDesc('id')->get()
            ->map(fn (Promotion $promotion) => [
                'id' => $promotion->id,
                'nombre' => $promotion->nombre,
                'porcentaje' => $promotion->porcentaje,
                'alcance' => $promotion->alcance,
                'categoria' => $promotion->category?->name,
                'productos' => $promotion->products->take(3)->pluck('nombre')->values(),
                'cantidad_productos' => $promotion->products->count(),
                'inicio_ms' => $promotion->fecha_inicio?->getTimestamp() * 1000 ?: null,
                'fin_ms' => $promotion->permanente ? null : ($promotion->fecha_fin?->getTimestamp() * 1000 ?: null),
                'canal_fisico' => $promotion->canal_fisico,
                'canal_web' => $promotion->canal_web,
                'canal_app' => $promotion->canal_app,
            ])->values();

        return response()->json([
            'promociones' => $promotions,
            'server_time_ms' => (int) floor(microtime(true) * 1000),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);
        $data = $this->validatedData($request);

        $promotion = DB::transaction(function () use ($data) {
            $promotion = Promotion::create($this->promotionAttributes($data));
            $this->syncRelations($promotion, $data);

            return $promotion;
        });

        $this->pricing->clearCache();

        return response()->json($this->loadPromotion($promotion), 201);
    }

    public function show(Request $request, Promotion $promotion)
    {
        $this->ensureAdmin($request);

        return $this->loadPromotion($promotion);
    }

    public function update(Request $request, Promotion $promotion)
    {
        $this->ensureAdmin($request);
        $data = $this->validatedData($request);

        DB::transaction(function () use ($promotion, $data) {
            $promotion->update($this->promotionAttributes($data));
            $this->syncRelations($promotion, $data);
        });

        $this->pricing->clearCache();

        return $this->loadPromotion($promotion);
    }

    public function destroy(Request $request, Promotion $promotion)
    {
        $this->ensureAdmin($request);
        $promotion->delete();
        $this->pricing->clearCache();

        return response()->noContent();
    }

    private function validatedData(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'porcentaje' => ['required', 'numeric', 'gt:0', 'lte:100'],
            'alcance' => ['required', Rule::in(['TODAS_CATEGORIAS', 'CATEGORIA', 'PRODUCTOS'])],
            'category_id' => [
                Rule::requiredIf(fn () => $request->input('alcance') === 'CATEGORIA'),
                'nullable',
                'integer',
                'exists:categories,id',
            ],
            'product_ids' => [
                Rule::requiredIf(fn () => $request->input('alcance') === 'PRODUCTOS'),
                'array',
            ],
            'product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
            'permanente' => ['required', 'boolean'],
            'fecha_inicio' => [
                Rule::requiredIf(fn () => !$request->boolean('permanente')),
                'nullable',
                'date',
            ],
            'fecha_fin' => [
                Rule::requiredIf(fn () => !$request->boolean('permanente')),
                'nullable',
                'date',
                'after:fecha_inicio',
            ],
            'activo' => ['required', 'boolean'],
            'todas_agencias' => ['required', 'boolean'],
            'agencia_ids' => [
                Rule::requiredIf(fn () => !$request->boolean('todas_agencias')),
                'array',
            ],
            'agencia_ids.*' => ['integer', 'distinct', 'exists:agencias,id'],
            'canal_fisico' => ['required', 'boolean'],
            'canal_web' => ['required', 'boolean'],
            'canal_app' => ['required', 'boolean'],
            'mostrar_en_ofertas' => ['required', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request) {
            if (!$request->boolean('canal_fisico') && !$request->boolean('canal_web') && !$request->boolean('canal_app')) {
                $validator->errors()->add('canales', 'Selecciona al menos un canal para la promoción.');
            }
            if ($request->input('alcance') === 'PRODUCTOS' && empty($request->input('product_ids', []))) {
                $validator->errors()->add('product_ids', 'Selecciona al menos un producto.');
            }
            if ($request->input('alcance') === 'PRODUCTOS') {
                $hasControlledProducts = Product::query()
                    ->whereIn('id', $request->input('product_ids', []))
                    ->whereIn('subcategory_id', $this->pricing->excludedSubcategoryIds())
                    ->exists();

                if ($hasControlledProducts) {
                    $validator->errors()->add(
                        'product_ids',
                        'Los medicamentos controlados no pueden incluirse en promociones.'
                    );
                }
            }
            if (!$request->boolean('todas_agencias') && empty($request->input('agencia_ids', []))) {
                $validator->errors()->add('agencia_ids', 'Selecciona al menos una sucursal.');
            }

            $percentage = (float) $request->input('porcentaje', 0);
            if ($percentage > 0 && $percentage <= 100) {
                $query = Product::query()->whereNotNull('precio')->where('precio', '>', 0);
                if ($request->input('alcance') === 'CATEGORIA' && $request->filled('category_id')) {
                    $query->where('category_id', $request->input('category_id'));
                } elseif ($request->input('alcance') === 'PRODUCTOS') {
                    $query->whereIn('id', $request->input('product_ids', []));
                } elseif ($request->input('alcance') !== 'TODAS_CATEGORIAS') {
                    $query->whereRaw('1 = 0');
                }

                $excludedIds = $this->pricing->excludedSubcategoryIds();
                if ($excludedIds) {
                    $query->where(fn ($allowed) => $allowed->whereNull('subcategory_id')
                        ->orWhereNotIn('subcategory_id', $excludedIds));
                }

                $minimumPrice = $query->min('precio');
                if ($minimumPrice !== null && Money::roundToTenth((float) $minimumPrice * (1 - $percentage / 100)) <= 0) {
                    $validator->errors()->add(
                        'porcentaje',
                        'El descuento es demasiado alto: dejaría al menos un producto con precio de venta Bs 0,00.'
                    );
                }
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    private function promotionAttributes(array $data): array
    {
        return [
            'nombre' => trim($data['nombre']),
            'descripcion' => isset($data['descripcion']) ? trim((string) $data['descripcion']) : null,
            'porcentaje' => round((float) $data['porcentaje'], 2),
            'alcance' => $data['alcance'],
            'category_id' => $data['alcance'] === 'CATEGORIA' ? $data['category_id'] : null,
            'fecha_inicio' => ($data['fecha_inicio'] ?? null) ?: null,
            'fecha_fin' => $data['permanente'] ? null : $data['fecha_fin'],
            'permanente' => (bool) $data['permanente'],
            'activo' => (bool) $data['activo'],
            'todas_agencias' => (bool) $data['todas_agencias'],
            'canal_fisico' => (bool) $data['canal_fisico'],
            'canal_web' => (bool) $data['canal_web'],
            'canal_app' => (bool) $data['canal_app'],
            'mostrar_en_ofertas' => (bool) $data['mostrar_en_ofertas'],
        ];
    }

    private function syncRelations(Promotion $promotion, array $data): void
    {
        $promotion->products()->sync($data['alcance'] === 'PRODUCTOS' ? ($data['product_ids'] ?? []) : []);
        $promotion->agencias()->sync($data['todas_agencias'] ? [] : ($data['agencia_ids'] ?? []));
    }

    private function loadPromotion(Promotion $promotion): Promotion
    {
        return $promotion->fresh([
            'category:id,name',
            'products:id,nombre',
            'agencias:id,nombre',
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless((int) $request->user()?->id === 1, 403, 'Solo el administrador puede gestionar promociones.');
    }
}
