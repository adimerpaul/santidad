<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\RelatedProductsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RelatedProductsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'related_test', 'database.connections.related_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('related_test');
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('composicion')->nullable();
            $table->string('descripcion')->nullable();
            $table->string('activo')->default('ACTIVO');
            $table->integer('category_id')->nullable();
            $table->integer('subcategory_id')->nullable();
            $table->boolean('en_oferta')->default(false);
            $table->decimal('precio', 10, 2)->default(10);
        });
    }

    private function product(int $id, string $composition, array $extra = []): Product
    {
        DB::table('products')->insert(array_merge([
            'id' => $id, 'nombre' => 'Producto '.$id, 'composicion' => $composition,
            'category_id' => 1, 'subcategory_id' => 1,
        ], $extra));
        return Product::findOrFail($id);
    }

    public function test_composition_and_dose_replace_the_old_category_only_selection(): void
    {
        $base = $this->product(1, 'Paracetamol 500 mg');
        $this->product(2, 'Paracetamol 500 mg', ['category_id' => 2]);
        $this->product(3, 'Paracetamol 1000 mg');
        $this->product(4, 'Omeprazol 20 mg');
        $this->product(5, 'Paracetamol 500 mg', ['activo' => 'INACTIVO']);
        $this->assertSame([2], app(RelatedProductsService::class)->find($base)->pluck('id')->all());
    }

    public function test_aliases_ranking_and_name_fallback_match_the_previous_store(): void
    {
        $base = $this->product(1, 'Paracetamol 500 mg');
        $this->product(2, 'Acetaminofen 500 mg', ['precio' => 8]);
        $this->product(3, 'Paracetamol 500 mg', ['precio' => 4]);
        $this->product(4, '', ['nombre' => 'Paracetamol 500 mg']);
        $this->assertSame([3, 2, 4], app(RelatedProductsService::class)->find($base)->pluck('id')->all());
    }

    public function test_products_without_composition_do_not_show_unrelated_category_items(): void
    {
        $base = $this->product(1, '');
        $this->product(2, 'Paracetamol 500 mg');
        $this->assertCount(0, app(RelatedProductsService::class)->find($base));
    }
}
