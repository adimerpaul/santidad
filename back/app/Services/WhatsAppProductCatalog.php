<?php

namespace App\Services;

use App\Models\Agencia;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WhatsAppProductCatalog
{
    public function __construct(private WhatsAppProductPolicy $policy, private WhatsAppIntentDetector $language,
        private PromotionPricingService $pricing)
    {
    }

    public function find(int $id): ?Product
    {
        $candidate = $this->policy->candidates()->find($id);

        return $candidate && $this->policy->allows($candidate) ? $candidate : null;
    }

    public function search(string $text): Collection
    {
        $terms = $this->terms($text);
        if (!$terms || !array_filter($terms, fn ($word) => strlen($word) >= 4 && !ctype_digit($word))) {
            return collect();
        }

        $scores = [];
        foreach ($this->policy->candidates()->select('id', 'nombre', 'marca', 'nombreComun')->cursor() as $candidate) {
            $candidateText = $this->normalizePresentation("$candidate->nombre $candidate->marca $candidate->nombreComun");
            $words = preg_split('/[^a-z0-9]+/', $candidateText);
            $score = 0;
            $strong = false;
            foreach ($terms as $term) {
                if (in_array($term, $words, true)) {
                    $score += 4;
                    $strong = $strong || !ctype_digit($term);
                    continue;
                }
                if (preg_match('/^\d+(?:ml|g|gr|mg)?$/', $term)) {
                    if (!preg_match('/\b' . $term . '(?:ml|g|gr|mg)?\b/', $candidateText)) {
                        continue 2;
                    }
                    $score += 4;
                } elseif (strlen($term) >= 5 && array_filter($words, fn ($word) => levenshtein($term, $word) === 1)) {
                    $score += 3;
                    $strong = true;
                }
            }
            if ($strong && $score >= max(3, count($terms) * 2)) {
                $scores[$candidate->id] = $score;
            }
        }
        arsort($scores);

        return collect(array_slice(array_keys($scores), 0, 15))->map(fn ($id) => $this->find($id))->filter()->take(6)->values();
    }

    public function isReference(string $text): bool
    {
        return $this->terms($text) === [];
    }

    private function terms(string $text): array
    {
        $text = $this->normalizePresentation(mb_substr($text, 0, 240));
        $terms = preg_split('/[^a-z0-9]+/', preg_replace('/\b(por|ahi|favor|tienen?|hay|cual|cuanto|cuesta|precio|del?|con|para|quedan?|quiero|busco|producto|disponibilidad|stock|en|los?|las?|una?|si|suc|agencia|sucursal|otra|otro|esa|ese|pedir|pedido|hacer|comprar|compra|vale|sale|costo|disponible|buenas)\b/', ' ', $text));

        return array_slice(array_values(array_unique(array_filter($terms, fn ($word) => strlen($word) >= 3 || ctype_digit($word)))), 0, 12);
    }

    private function normalizePresentation(string $text): string
    {
        return preg_replace_callback('/\b(\d+)\s*(ml|mg|gr|g)\b/', fn ($part) => $part[1] . ($part[2] === 'gr' ? 'g' : $part[2]),
            $this->language->normalize($text));
    }

    public function availability(Product $product, Agencia $branch, int $quantity): ?bool
    {
        $column = 'cantidadSucursal' . $branch->id;
        $stock = Schema::hasColumn('products', $column) ? $product->getAttribute($column) : null;
        if ($stock === null && Schema::hasTable('inventario')) {
            $rows = DB::table('inventario')->where('product_id', $product->id)->where('agencia_id', $branch->id)->limit(2)->pluck('cantidad');
            $stock = $rows->count() === 1 ? $rows->first() : null;
        }

        return is_numeric($stock) ? (float) $stock >= max(1, $quantity) : null;
    }

    public function price(Product $product, Agencia $branch): ?string
    {
        if (config('services.whatsapp_webhook.catalog_price_channel') !== 'physical' || !is_numeric($product->precio) || $product->precio <= 0) {
            return null;
        }

        $price = $this->pricing->resolve($product, 'physical', $branch->id)['precio_venta'];
        return is_numeric($price) && $price > 0 ? number_format($price, 2, ',', '.') : null;
    }
}
