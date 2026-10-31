<?php

namespace App\Services;

use App\Models\InternalCombo;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Validation\ValidationException;

class InternalComboService
{
    private ?array $excluded = null;
    private function excludedIds(): array { return $this->excluded ??= app(PromotionPricingService::class)->excludedSubcategoryIds(); }

    public function assertPriceAllowed(Product $product, float $price): void
    {
        if ($product->activo !== 'ACTIVO') {
            throw new \DomainException('El producto '.$product->nombre.' del combo ya no está activo.');
        }
        if (in_array((int) $product->subcategory_id, $this->excludedIds(), true)
            && Money::toCents($price) !== Money::toCents(Money::roundToTenth($product->precio))) {
            throw new \DomainException('El producto '.$product->nombre.' está excluido de descuentos. Actualiza el combo antes de venderlo.');
        }
    }
    public function assertAvailable(InternalCombo $combo, string $channel): void
    {
        if (!$combo->activo || ($combo->canal !== 'ambos' && $combo->canal !== $channel)
            || (!$combo->permanente && (!$combo->fecha_inicio || !$combo->fecha_fin
                || $combo->fecha_inicio > now()->format('Y-m-d H:i:s') || $combo->fecha_fin <= now()->format('Y-m-d H:i:s')))) {
            throw new \DomainException('El combo '.$combo->nombre.' no está disponible para esta venta.');
        }
    }

    public function buildItems(array $data): array
    {
        $products = Product::whereIn('id', array_column($data['items'], 'product_id'))->get()->keyBy('id');
        $excluded = $this->excludedIds();
        $items = [];
        foreach ($data['items'] as $line) {
            $product = $products->get($line['product_id']);
            if (!$product || $product->activo !== 'ACTIVO' || (float) $product->precio <= 0) {
                throw ValidationException::withMessages(['items' => 'Todos los productos del combo deben estar activos y tener precio válido.']);
            }
            $base = Money::roundToCents($product->precio);
            $controlled = in_array((int) $product->subcategory_id, $excluded, true);
            $price = $data['modo'] !== 'producto'
                ? Money::roundToTenth($base * (1 - ($controlled || $data['modo'] === 'total' ? 0 : (float) $data['porcentaje']) / 100))
                : Money::roundToTenth($line['precio_unitario']);
            if ($price <= 0 || $price > Money::roundToTenth($base) || ($controlled && Money::toCents($price) !== Money::toCents(Money::roundToTenth($base)))) {
                throw ValidationException::withMessages(['items' => 'Revisa el precio de '.$product->nombre.'. Debe ser positivo, sin superar el normal; los medicamentos controlados conservan su exclusión.']);
            }
            $items[] = ['product_id' => $product->id, 'cantidad' => $line['cantidad'], 'precio_base' => $base, 'precio_unitario' => $price, '_fixed' => $controlled];
        }
        if ($data['modo'] === 'total') $items = $this->allocateTotal($items, Money::toCents($data['precio_objetivo']));
        return array_map(function ($item) { unset($item['_fixed']); return $item; }, $items);
    }

    private function allocateTotal(array $items, int $target): array
    {
        if ($target % 10 !== 0) throw ValidationException::withMessages(['precio_objetivo' => 'El total debe ser múltiplo de Bs 0,10.']);
        $fixed = 0; $base = 0; $minimum = 0;
        foreach ($items as $item) {
            $value = Money::toCents(Money::roundToTenth($item['precio_base'])) * $item['cantidad'];
            if ($item['_fixed']) $fixed += $value;
            else { $base += $value; $minimum += 10 * $item['cantidad']; }
        }
        if ($target < $fixed + $minimum || $target > $fixed + $base) throw ValidationException::withMessages(['precio_objetivo' => 'El total no puede superar el precio normal ni dejar productos en cero; los controlados mantienen su precio.']);
        $prices = []; $caps = []; $quantities = []; $ideal = [];
        foreach ($items as $index => &$item) {
            if ($item['_fixed']) continue;
            $cap = intdiv(Money::toCents(Money::roundToTenth($item['precio_base'])), 10);
            $ideal[$index] = $base ? $cap * ($target - $fixed) / $base : $cap;
            $prices[$index] = max(1, min($cap, (int) round($ideal[$index])));
            $caps[$index] = $cap; $quantities[$index] = $item['cantidad'];
        }
        unset($item);
        $remaining = intdiv($target - $fixed, 10);
        foreach ($prices as $index => $price) $remaining -= $price * $quantities[$index];
        // Largest remainder redistribution, measured in existing Bs 0.10 unit prices.
        while ($remaining !== 0) {
            $direction = $remaining > 0 ? 1 : -1; $best = null; $cost = INF;
            foreach ($prices as $index => $price) {
                if ($quantities[$index] > abs($remaining) || $price + $direction < 1 || $price + $direction > $caps[$index]) continue;
                $score = ($price + $direction - $ideal[$index]) ** 2 - ($price - $ideal[$index]) ** 2;
                if ($score < $cost) { $best = $index; $cost = $score; }
            }
            if ($best === null) break;
            $prices[$best] += $direction; $remaining -= $direction * $quantities[$best];
        }
        // Different item quantities may need one price to rise while another falls.
        if ($remaining !== 0) {
            $ids = array_keys($prices);
            foreach ($ids as $a) {
                foreach ($ids as $b) {
                    if ($a >= $b) continue;
                    $solution = $this->pairAdjustment($quantities[$a], $quantities[$b], $remaining, 1 - $prices[$a], $caps[$a] - $prices[$a], 1 - $prices[$b], $caps[$b] - $prices[$b]);
                    if ($solution !== null) { $prices[$a] += $solution[0]; $prices[$b] += $solution[1]; $remaining = 0; break 2; }
                }
            }
        }
        if ($remaining !== 0) throw ValidationException::withMessages(['precio_objetivo' => 'No se pudo repartir exactamente ese total con estas cantidades y el redondeo a Bs 0,10. Ajusta el total o las cantidades.']);
        foreach ($prices as $index => $price) $items[$index]['precio_unitario'] = $price / 10;
        return $items;
    }

    private function pairAdjustment(int $a, int $b, int $target, int $minX, int $maxX, int $minY, int $maxY): ?array
    {
        $oldR = $a; $r = $b; $oldS = 1; $s = 0; $oldT = 0; $t = 1;
        while ($r) { $q = intdiv($oldR, $r); [$oldR, $r] = [$r, $oldR - $q * $r]; [$oldS, $s] = [$s, $oldS - $q * $s]; [$oldT, $t] = [$t, $oldT - $q * $t]; }
        if ($target % $oldR !== 0) return null;
        $x = $oldS * intdiv($target, $oldR); $y = $oldT * intdiv($target, $oldR); $dx = intdiv($b, $oldR); $dy = intdiv($a, $oldR);
        $low = max((int) ceil(($minX - $x) / $dx), (int) ceil(($y - $maxY) / $dy));
        $high = min((int) floor(($maxX - $x) / $dx), (int) floor(($y - $minY) / $dy));
        if ($low > $high) return null;
        $k = max($low, min($high, (int) round(($dy * $y - $dx * $x) / ($dx * $dx + $dy * $dy))));
        return [$x + $dx * $k, $y - $dy * $k];
    }

    public function present(InternalCombo $combo, ?int $agency = null): array
    {
        $base = 0; $total = 0; $available = PHP_INT_MAX;
        $items = [];
        $excluded = $this->excludedIds();
        foreach ($combo->items as $item) {
            $p = $item->product;
            $stock = $p && $p->activo === 'ACTIVO' ? max(0, min((int) $p->cantidad, (int) ($p->{'cantidadSucursal'.$agency} ?? 0))) : 0;
            if ($p && in_array((int) $p->subcategory_id, $excluded, true)
                && Money::toCents($item->precio_unitario) !== Money::toCents(Money::roundToTenth($p->precio))) $stock = 0;
            $available = min($available, intdiv($stock, max(1, $item->cantidad)));
            $base += Money::toCents($item->precio_base) * $item->cantidad;
            $total += Money::toCents($item->precio_unitario) * $item->cantidad;
            $items[] = ['product_id' => $item->product_id, 'cantidad' => $item->cantidad, 'precio_base' => $item->precio_base, 'precio_unitario' => $item->precio_unitario,
                'nombre' => $p?->nombre ?? 'Producto eliminado', 'imagen' => $p?->imagen ?? 'productDefault.jpg',
                'precio_actual' => $p?->precio, 'activo_producto' => $p?->activo === 'ACTIVO', 'stock' => $stock,
                'excluido' => $p && in_array((int) $p->subcategory_id, $excluded, true)];
        }
        $data = $combo->attributesToArray();
        return $data + ['items' => $items, 'precio_base' => Money::fromCents($base), 'precio_total' => Money::fromCents($total),
            'descuento_total' => Money::fromCents(max(0, $base - $total)), 'porcentaje_efectivo' => $base ? round(max(0, $base - $total) * 100 / $base, 4) : 0,
            'combos_disponibles' => $items ? $available : 0];
    }

    // Returns official combo prices by basket line index. Non-combo requests never
    // query the combo tables, preserving ordinary sales during deployment.
    public function resolveLines(array $lines, string $quantityField, string $channel, bool $lock = false): array
    {
        $tagged = collect($lines)->filter(fn ($p) => !empty($p['combo_id']) || !empty($p['combo_grupo']));
        if ($tagged->isEmpty()) return [];
        if (collect($lines)->contains(fn ($p) => !empty($p['online_order_number']))) throw new \DomainException('Los combos internos no se mezclan con pedidos online.');
        $normal = collect($lines)->filter(fn ($p) => empty($p['combo_id']) && empty($p['combo_grupo']));
        if ($normal->pluck('id')->unique()->count() !== $normal->count()) throw new \DomainException('Hay productos sueltos repetidos en la canasta.');
        $groups = $tagged->groupBy('combo_grupo', true);
        if ($groups->count() > 20 || $groups->has('')) throw new \DomainException('Revisa los grupos de combos de la canasta.');
        $query = InternalCombo::with('items')->whereIn('id', $tagged->pluck('combo_id')->unique())->orderBy('id');
        if ($lock) $query->lockForUpdate();
        $combos = $query->get()->keyBy('id');
        $result = [];
        foreach ($groups as $group => $groupLines) {
            $first = $groupLines->first();
            $combo = $combos->get($first['combo_id'] ?? null);
            if (!$combo) throw new \DomainException('Un combo ya no existe. Retíralo y vuelve a cargarlo.');
            $this->assertAvailable($combo, $channel);
            $count = (int) ($first['combo_cantidad'] ?? 0);
            if ($count < 1 || $count > 100 || (int) ($first['combo_version'] ?? 0) !== $combo->version
                || $groupLines->count() !== $combo->items->count() || $groupLines->pluck('id')->unique()->count() !== $groupLines->count()) {
                throw new \DomainException('El combo '.$combo->nombre.' cambió o está incompleto. Retíralo y vuelve a cargarlo.');
            }
            $items = $combo->items->keyBy('product_id');
            foreach ($groupLines as $index => $line) {
                $item = $items->get($line['id']);
                if (!$item || (int) ($line['combo_id'] ?? 0) !== $combo->id || (int) ($line['combo_version'] ?? 0) !== $combo->version
                    || (int) ($line['combo_cantidad'] ?? 0) !== $count || (int) $line[$quantityField] !== $item->cantidad * $count) {
                    throw new \DomainException('El combo '.$combo->nombre.' debe venderse completo, con sus cantidades configuradas.');
                }
                $result[$index] = [
                    'precio_original' => $item->precio_base, 'precio_venta' => $item->precio_unitario,
                    'porcentaje' => $item->precio_base ? round(max(0, $item->precio_base - $item->precio_unitario) * 100 / $item->precio_base, 4) : 0,
                    'promocion_id' => null, 'promocion_nombre' => 'Combo '.$combo->nombre,
                    'combo_snapshot' => ['combo_id' => $combo->id, 'combo_nombre' => $combo->nombre, 'combo_grupo' => $group,
                        'combo_cantidad' => $count, 'combo_version' => $combo->version],
                ];
            }
        }
        return $result;
    }
}
