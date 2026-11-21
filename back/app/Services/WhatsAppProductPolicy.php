<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class WhatsAppProductPolicy
{
    public const CLASSIFICATIONS = ['non_regulated', 'otc', 'prescription', 'controlled', 'pending'];

    public function __construct(private WhatsAppIntentDetector $language)
    {
    }

    public function candidates(): Builder
    {
        return Product::where('whatsapp_classification', 'non_regulated')->whereIn('activo', ['ACTIVO', '1', 'TRUE', 'true', 'ON', 'YES']);
    }

    public function allows(Product $product): bool
    {
        return $product->whatsapp_classification === 'non_regulated' && $product->activo === 'ACTIVO'
            && !$this->isRestricted($product);
    }

    public function isRestricted(Product $product): bool
    {
        $name = $product->subcategory_id ? DB::table('subcategories')->where('id', $product->subcategory_id)->value('name') : '';

        return (bool) preg_match('/\b(controlad[oa]s?|psicotropic[oa]s?|estupefacientes?|antibioticos?|receta)\b/', $this->language->normalize((string) $name))
            || $this->language->detect((string) $product->nombre)[0] === 'farmaceutica';
    }
}
