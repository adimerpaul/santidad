<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RelatedProductsService
{
    public function find(Product $product): Collection
    {
        $composition = trim((string) $product->composicion);
        if ($composition === '') return collect();

        $query = $this->normalize($composition, false);
        $tokens = array_slice(array_values(array_unique(array_filter(explode(' ', $query), fn ($t) => strlen($t) >= 3))), 0, 5);
        $searches = array_unique(array_filter(array_merge([$query], $tokens)));
        $pool = collect();
        // Mismo conjunto de búsquedas que la tienda anterior, con un límite por búsqueda.
        foreach ($searches as $search) {
            $pool = $pool->merge($this->activeProducts()->where(function ($q) use ($search) {
                $q->where('nombre', 'like', '%'.$search.'%')
                    ->orWhere('descripcion', 'like', '%'.$search.'%')
                    ->orWhere('composicion', 'like', '%'.$search.'%');
            })->orderByDesc('en_oferta')->orderByDesc('id')->limit(200)->get());
        }
        if ($product->subcategory_id) {
            $pool = $pool->merge($this->activeProducts()->where('subcategory_id', $product->subcategory_id)
                ->orderByDesc('en_oferta')->orderByDesc('id')->limit(200)->get());
        }

        return $this->rank($product, $pool);
    }

    private function activeProducts()
    {
        return Product::whereIn('activo', ['ACTIVO', 1, '1', 'true', 'TRUE']);
    }

    public function rank(Product $product, Collection $candidates): Collection
    {
        $composition = trim((string) $product->composicion);
        if ($composition === '') return collect();
        $base = $this->normalize($composition);
        $dose = $this->doseKey($composition);
        return $candidates->unique('id')->reject(fn ($p) => (int) $p->id === (int) $product->id)
            ->map(function ($p) use ($base, $dose, $product) {
                $hasComposition = trim((string) $p->composicion) !== '';
                $text = $hasComposition ? $p->composicion : $p->nombre;
                if ($dose !== '' && $this->doseKey($text) !== $dose) return null;
                $similarity = $this->similarity($base, $this->normalize($text));
                $score = $hasComposition ? $similarity : min($similarity + .2, .5);
                if ($similarity <= 0 || $score < ($hasComposition ? .55 : .35)) return null;
                return ['product' => $p, 'score' => $score,
                    'same_subcategory' => $product->subcategory_id && (int) $p->subcategory_id === (int) $product->subcategory_id ? 1 : 0];
            })->filter()->sort(function ($a, $b) {
                return ($b['score'] <=> $a['score'])
                    ?: ($b['same_subcategory'] <=> $a['same_subcategory'])
                    ?: ((float) $a['product']->precio <=> (float) $b['product']->precio);
            })->take(50)->pluck('product')->values();
    }

    private function normalize(string $text, bool $similarity = true): string
    {
        $text = Str::lower(Str::ascii($text));
        $text = preg_replace(['/([a-z])(\d)/i', '/(\d)([a-z])/i'], '$1 $2', $text);
        if ($similarity) {
            foreach (['ibuproneo' => 'ibuprofeno', 'acetaminofen' => 'paracetamol',
                'dc' => 'compresion directa', 'sr' => 'liberacion sostenida',
                'gr' => 'gastroresistente', 'reistente' => 'gastroresistente'] as $from => $to) {
                $text = preg_replace('/\b'.$from.'\b/', $to, $text);
            }
            $text = preg_replace('/\bgastro\s+resistente\b/', 'gastroresistente', $text);
        }
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        if ($similarity) $text = preg_replace('/\b(comprimidos?|tabletas?|capsulas?|solucion|jarabe|unguento|crema|spray)\b/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function doseKey(string $text): string
    {
        preg_match_all('/\b(\d+(?:[.,]\d+)?)\s*(mg|ml|mcg|µg|ug|g|gr|kg|l|ui|%)\b/iu', $text, $matches, PREG_SET_ORDER);
        $doses = array_map(fn ($m) => (string) (float) str_replace(',', '.', $m[1]).Str::lower($m[2]), $matches);
        sort($doses, SORT_STRING);
        return implode('|', $doses);
    }

    private function similarity(string $a, string $b): float
    {
        if ($a === '' || $b === '') return 0;
        $left = array_values(array_unique(explode(' ', $a)));
        $right = array_values(array_unique(explode(' ', $b)));
        $used = [];
        $intersection = 0;
        foreach ($left as $token) {
            foreach ($right as $i => $candidate) {
                if (isset($used[$i])) continue;
                $length = max(strlen($token), strlen($candidate));
                if (levenshtein($token, $candidate) <= ($length <= 4 ? 1 : ($length <= 7 ? 2 : 3))) {
                    $used[$i] = true;
                    $intersection++;
                    break;
                }
            }
        }
        return $intersection / max(1, count(array_unique(array_merge($left, $right))));
    }
}
