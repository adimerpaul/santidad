<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\WhatsAppProductPolicy;
use Illuminate\Console\Command;

class ClassifyWhatsAppProduct extends Command
{
    protected $signature = 'whatsapp:classify-product {product} {classification} {--verified-otc : Indica que se verificó la clasificación oficial}';
    protected $description = 'Clasifica un producto para la política comercial de WhatsApp';

    public function handle(WhatsAppProductPolicy $policy): int
    {
        $classification = $this->argument('classification');
        $product = Product::find($this->argument('product'));
        if (!$product || !in_array($classification, WhatsAppProductPolicy::CLASSIFICATIONS, true)) {
            $this->error('Indica un producto existente y una clasificación válida.');
            return self::FAILURE;
        }
        if ($classification === 'otc' && !$this->option('verified-otc')) {
            $this->error('Verifica la clasificación oficial antes de marcarlo como OTC.');
            return self::FAILURE;
        }
        if ($classification === 'non_regulated' && $policy->isRestricted($product)) {
            $this->error('Este producto no puede habilitarse para el catálogo de WhatsApp.');
            return self::FAILURE;
        }
        $product->whatsapp_classification = $classification;
        $product->save();
        $this->info('Clasificación guardada.');

        return self::SUCCESS;
    }
}
