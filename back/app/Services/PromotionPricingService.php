<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Promotion;
use App\Models\Subcategory;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PromotionPricingService
{
    private array $requestCache = [];
    private ?array $promotionExcludedSubcategoryIds = null;

    public function resolve(Product $product, string $channel, ?int $agenciaId = null): array
    {
        // Conservar los centavos del precio base; redondear a décimas solo al cobrar.
        $precioOriginal = Money::roundToCents($product->precio);
        $porcentajeBase = $this->normalizePercentage($product->porcentaje ?? 0);
        $mostrarEnOfertas = false;

        $candidates = collect();
        if ($porcentajeBase > 0) {
            $candidates->push([
                'id' => null,
                'nombre' => 'Descuento permanente del producto',
                'porcentaje' => $porcentajeBase,
                'especificidad' => 2,
                'origen' => 'PRODUCTO',
            ]);
        }

        $activePromotions = $this->isExcludedFromPromotions($product)
            ? collect()
            : $this->activePromotions($channel, $agenciaId);

        foreach ($activePromotions as $promotion) {
            $esProducto = $promotion->alcance === 'PRODUCTOS'
                && $promotion->products->contains('id', $product->id);
            $esCategoria = $promotion->alcance === 'CATEGORIA'
                && (int) $promotion->category_id === (int) $product->category_id;
            $esTodas = $promotion->alcance === 'TODAS_CATEGORIAS';

            if (!$esProducto && !$esCategoria && !$esTodas) {
                continue;
            }

            $mostrarEnOfertas = $mostrarEnOfertas || (bool) $promotion->mostrar_en_ofertas;

            $candidates->push([
                'id' => $promotion->id,
                'nombre' => $promotion->nombre,
                'porcentaje' => $this->normalizePercentage($promotion->porcentaje),
                'especificidad' => $esProducto ? 2 : ($esCategoria ? 1 : 0),
                'origen' => $promotion->alcance,
            ]);
        }

        $winner = $candidates
            ->sort(fn (array $left, array $right) =>
                ($right['porcentaje'] <=> $left['porcentaje'])
                ?: ($right['especificidad'] <=> $left['especificidad'])
                ?: ((int) $right['id'] <=> (int) $left['id'])
            )
            ->first();

        $porcentaje = (float) ($winner['porcentaje'] ?? 0);
        $precioVenta = Money::roundToTenth($precioOriginal * (1 - ($porcentaje / 100)));

        return [
            'precio_original' => $precioOriginal,
            'precio_venta' => $precioVenta,
            'porcentaje' => $porcentaje,
            'promocion_id' => $winner['id'] ?? null,
            'promocion_nombre' => $winner['nombre'] ?? null,
            'origen' => $winner['origen'] ?? null,
            'mostrar_en_ofertas' => $mostrarEnOfertas,
        ];
    }

    public function clearCache(): void
    {
        $version = (int) Cache::get('promotions_cache_version', 1);
        Cache::forever('promotions_cache_version', $version + 1);
        $this->requestCache = [];
        $this->promotionExcludedSubcategoryIds = null;
    }

    public function activeScopeIds(string $channel, ?int $agenciaId = null): array
    {
        $promotions = $this->activePromotions($channel, $agenciaId)
            ->where('mostrar_en_ofertas', true);

        return [
            'all_categories' => $promotions->contains('alcance', 'TODAS_CATEGORIAS'),
            'product_ids' => $promotions
                ->where('alcance', 'PRODUCTOS')
                ->flatMap(fn (Promotion $promotion) => $promotion->products->pluck('id'))
                ->unique()
                ->values()
                ->all(),
            'category_ids' => $promotions
                ->where('alcance', 'CATEGORIA')
                ->pluck('category_id')
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'excluded_subcategory_ids' => $this->excludedSubcategoryIds(),
        ];
    }

    public function excludedSubcategoryIds(): array
    {
        if ($this->promotionExcludedSubcategoryIds !== null) {
            return $this->promotionExcludedSubcategoryIds;
        }

        return $this->promotionExcludedSubcategoryIds = Subcategory::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', ['medicamentos controlados'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function activePromotions(string $channel, ?int $agenciaId): Collection
    {
        $channelColumn = match ($channel) {
            'web' => 'canal_web',
            'app' => 'canal_app',
            default => 'canal_fisico',
        };
        $version = (int) Cache::get('promotions_cache_version', 1);
        $cacheKey = "promotions_active_{$version}_{$channelColumn}_" . ($agenciaId ?: 'todas');

        if (array_key_exists($cacheKey, $this->requestCache)) {
            return $this->requestCache[$cacheKey];
        }

        $candidates = Cache::remember($cacheKey, now()->addSeconds(30), function () use ($channelColumn, $agenciaId) {
            return Promotion::query()
                ->with('products:id')
                ->where('activo', true)
                ->where($channelColumn, true)
                ->where(function ($query) use ($agenciaId) {
                    $query->where('todas_agencias', true);
                    if ($agenciaId) {
                        $query->orWhereHas('agencias', fn ($agencyQuery) => $agencyQuery->where('agencias.id', $agenciaId));
                    }
                })
                ->get();
        });

        $ahora = now();

        return $this->requestCache[$cacheKey] = $candidates
            ->filter(fn (Promotion $promotion) =>
                (!$promotion->fecha_inicio || $promotion->fecha_inicio->lte($ahora))
                && ($promotion->permanente || !$promotion->fecha_fin || $promotion->fecha_fin->gte($ahora))
            )
            ->values();
    }

    private function normalizePercentage(int|float|string|null $value): float
    {
        return max(0, min(100, round((float) ($value ?? 0), 2)));
    }

    private function isExcludedFromPromotions(Product $product): bool
    {
        return $product->subcategory_id
            && in_array((int) $product->subcategory_id, $this->excludedSubcategoryIds(), true);
    }
}
