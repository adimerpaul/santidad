<?php

namespace App\Services;

use App\Models\Product;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class TraePricingService
{
    // Cache only inside the current HTTP request, never across price changes.
    public function snapshot(): array
    {
        $attributes = request()->attributes;
        if (!$attributes->has('trae_pricing')) {
            $settings = DB::table('trae_settings')->find(1);
            $rules = ($settings->fuente_precios ?? 'propio') === 'propio'
                ? DB::table('trae_discount_rules')->where('activo', true)->get() : collect();
            $sourcePricing = app(PromotionPricingService::class);
            $attributes->set('trae_pricing', [
                'settings' => $settings,
                'products' => $rules->whereNotNull('product_id')->keyBy('product_id'),
                'categories' => $rules->whereNotNull('category_id')->keyBy('category_id'),
                'at' => now()->format('Y-m-d H:i:s'),
                'excluded' => $sourcePricing->excludedSubcategoryIds(),
                // Reuse the normal pricing service for every product in this request.
                'source_pricing' => $sourcePricing,
            ]);
        }
        return $attributes->get('trae_pricing');
    }

    public function clearCache(): void
    {
        request()->attributes->remove('trae_pricing');
    }

    public function commissionPercentage(): float
    {
        return (float) ($this->snapshot()['settings']->comision_porcentaje ?? 12);
    }

    public function ruleIsEffective(object $rule, string $at): bool
    {
        return (bool) ($rule->permanente ?? true) ||
            (!empty($rule->fecha_inicio) && !empty($rule->fecha_fin) && $rule->fecha_inicio <= $at && $rule->fecha_fin > $at);
    }

    // Only two rules can affect one product. Check their future transitions as well
    // as the current price so scheduling/expiry cannot introduce a zero price later.
    public function validationInstants(Product $product): array
    {
        $snapshot = $this->snapshot();
        $instants = [$snapshot['at'] => true];
        foreach ([$snapshot['products']->get($product->id), $snapshot['categories']->get($product->category_id)] as $rule) {
            if (!$rule || ($rule->permanente ?? true)) continue;
            foreach (['fecha_inicio', 'fecha_fin'] as $field) {
                if (!empty($rule->$field) && $rule->$field > $snapshot['at']) $instants[$rule->$field] = true;
            }
        }
        return array_keys($instants);
    }

    public function resolve(Product $product, ?string $at = null): array
    {
        $snapshot = $this->snapshot();
        $at ??= $snapshot['at'];
        $source = $snapshot['settings']->fuente_precios ?? 'propio';
        if ($source !== 'propio') {
            $pricing = $snapshot['source_pricing']->resolve($product, $source, $snapshot['settings']->fuente_agencia_id ?? null);
            return array_merge($pricing, [
                'origen' => 'HEREDADO_'.strtoupper($source),
                'excluido' => in_array((int) $product->subcategory_id, $snapshot['excluded'], true),
                'comision_porcentaje' => $this->commissionPercentage(),
                'comision_monto' => Money::roundToCents($pricing['precio_venta'] * $this->commissionPercentage() / 100),
            ]);
        }
        $rule = null;
        foreach ([$snapshot['products']->get($product->id), $snapshot['categories']->get($product->category_id)] as $candidate) {
            if ($candidate && $this->ruleIsEffective($candidate, $at)) { $rule = $candidate; break; }
        }
        $excluded = in_array((int) $product->subcategory_id, $snapshot['excluded'], true);
        $percentage = $excluded ? 0 : (float) ($rule->porcentaje ?? $snapshot['settings']->descuento_general ?? 0);
        $original = Money::roundToCents($product->precio);
        $price = Money::roundToTenth($original * (1 - $percentage / 100));
        return [
            'precio_original' => $original,
            'precio_venta' => $price,
            'porcentaje' => $percentage,
            // TRAE rules are not promotion IDs: avoid mixing their namespaces.
            'promocion_id' => null,
            'promocion_nombre' => $percentage > 0 ? 'Descuento TRAE' : null,
            'origen' => $excluded ? 'EXCLUIDO' : ($rule ? ($rule->product_id ? 'PRODUCTO' : 'CATEGORIA') : 'GENERAL'),
            'excluido' => $excluded,
            'comision_porcentaje' => $this->commissionPercentage(),
            'comision_monto' => Money::roundToCents($price * $this->commissionPercentage() / 100),
        ];
    }
}
