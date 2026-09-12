<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\PromotionPricingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PromotionScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'promotion_test', 'database.connections.promotion_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'cache.default' => 'array']);
        DB::purge('promotion_test');
        Cache::flush();
        foreach (['categories', 'subcategories'] as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });
        }
        Schema::create('agencias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('status')->default('ACTIVO');
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->decimal('precio', 10, 2);
            $table->decimal('porcentaje', 5, 2)->default(0);
            $table->unsignedInteger('category_id')->nullable();
            $table->unsignedInteger('subcategory_id')->nullable();
            $table->string('activo')->default('ACTIVO');
            $table->boolean('en_oferta')->default(false);
        });
        (require database_path('migrations/2026_09_08_000002_create_promotions_tables.php'))->up();
        (require database_path('migrations/2026_09_08_000004_add_mostrar_en_ofertas_to_promotions_table.php'))->up();
        DB::table('categories')->insert([['id' => 1, 'name' => 'Salud'], ['id' => 2, 'name' => 'Belleza']]);
        DB::table('subcategories')->insert(['id' => 1, 'name' => 'Medicamentos Controlados']);
        DB::table('agencias')->insert([['id' => 1, 'nombre' => 'Matriz'], ['id' => 2, 'nombre' => 'Sucursal']]);
        foreach ([[1, 1, null, 100], [2, 2, null, 100], [3, 1, 1, 0.1], [4, null, null, 100]] as [$id, $category, $subcategory, $price]) {
            DB::table('products')->insert([
                'id' => $id, 'nombre' => 'Producto '.$id, 'precio' => $price,
                'category_id' => $category, 'subcategory_id' => $subcategory,
            ]);
        }
        $this->actingAs((new User())->forceFill(['id' => 1]), 'sanctum');
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'nombre' => 'Descuento general', 'porcentaje' => 20,
            'alcance' => 'TODAS_CATEGORIAS', 'category_id' => null, 'product_ids' => [],
            'permanente' => true, 'fecha_inicio' => null, 'fecha_fin' => null,
            'activo' => true, 'todas_agencias' => true, 'agencia_ids' => [],
            'canal_fisico' => true, 'canal_web' => true, 'canal_app' => true,
            'mostrar_en_ofertas' => true,
        ], $overrides);
    }

    private function createPromotion(array $overrides = []): int
    {
        return $this->postJson('/api/promotions', $this->payload($overrides))->assertCreated()->json('id');
    }

    public function test_base_price_cents_survive_saving_and_manual_discount_checkout(): void
    {
        $product = Product::findOrFail(1);
        $product->timestamps = false;
        $product->precio = 0.46;
        $product->porcentaje = 8;
        $product->save();
        $this->assertEquals(0.46, $product->fresh()->precio);

        $this->postJson('/api/verificar-stock-venta', [
            'agencia_id' => 1,
            'productos' => [['id' => 1, 'cantidadVenta' => 0]],
        ])->assertOk()->assertJsonPath('precios.0.precio', 0.46)
            ->assertJsonPath('precios.0.precioVenta', 0.4);
    }

    public function test_promotion_discount_uses_cents_and_competes_with_manual_discount(): void
    {
        DB::table('products')->where('id', 1)->update(['precio' => 0.46, 'porcentaje' => 5]);
        $promotionId = $this->createPromotion(['porcentaje' => 8]);
        $pricing = app(PromotionPricingService::class);
        foreach (['physical', 'web', 'app'] as $channel) {
            $price = $pricing->resolve(Product::findOrFail(1), $channel, 1);
            $this->assertEquals(0.46, $price['precio_original']);
            $this->assertEquals(0.4, $price['precio_venta']);
            $this->assertEquals(8, $price['porcentaje']);
            $this->assertSame($promotionId, $price['promocion_id']);
        }

        $web = collect($this->getJson('/api/productos?agencia_id=1')->assertOk()->json('data'))->firstWhere('id', 1);
        $app = collect($this->getJson('/api/app/productos?agencia_id=1')->assertOk()->json('data'))->firstWhere('id', 1);
        $this->assertEquals(0.46, $web['precio']);
        $this->assertEquals(0.4, $web['precioVenta']);
        $this->assertEquals(0.4, $app['precio']);

        DB::table('products')->where('id', 1)->update(['porcentaje' => 30]);
        $price = $pricing->resolve(Product::findOrFail(1), 'physical', 1);
        $this->assertEquals(30, $price['porcentaje']);
        $this->assertEquals(0.3, $price['precio_venta']);
        $this->assertNull($price['promocion_id']);
    }

    public function test_offers_can_be_disabled_and_reenabled_for_a_physical_only_promotion(): void
    {
        $payload = $this->payload(['canal_web' => false, 'canal_app' => false]);
        $id = $this->postJson('/api/promotions', $payload)->assertCreated()->json('id');
        $payload['mostrar_en_ofertas'] = false;
        $this->putJson('/api/promotions/'.$id, $payload)->assertOk()->assertJsonPath('mostrar_en_ofertas', false);
        $this->getJson('/api/promotions/'.$id)->assertOk()->assertJsonPath('mostrar_en_ofertas', false);
        $payload['mostrar_en_ofertas'] = true;
        $this->putJson('/api/promotions/'.$id, $payload)->assertOk()->assertJsonPath('mostrar_en_ofertas', true);
        $this->assertSame([], app(PromotionPricingService::class)->activeScopeIds('web', 1)['category_ids']);
        $this->assertFalse(app(PromotionPricingService::class)->activeScopeIds('web', 1)['all_categories']);
    }

    public function test_all_categories_include_future_products_and_preserve_exclusions_channels_and_branches(): void
    {
        $id = $this->createPromotion(['todas_agencias' => false, 'agencia_ids' => [1], 'canal_app' => false]);
        DB::table('categories')->insert(['id' => 5, 'name' => 'Nueva categoría']);
        DB::table('products')->insert(['id' => 5, 'nombre' => 'Nuevo', 'precio' => 100, 'category_id' => 5]);
        $pricing = app(PromotionPricingService::class);
        foreach ([1, 2, 4, 5] as $productId) {
            $product = Product::findOrFail($productId);
            $this->assertEquals(80, $pricing->resolve($product, 'fisico', 1)['precio_venta']);
            $this->assertSame($id, $pricing->resolve($product, 'web', 1)['promocion_id']);
            $this->assertEquals(100, $pricing->resolve($product, 'app', 1)['precio_venta']);
            $this->assertEquals(100, $pricing->resolve($product, 'web', 2)['precio_venta']);
        }
        $this->assertNull($pricing->resolve(Product::findOrFail(3), 'fisico', 1)['promocion_id']);
        $this->assertFalse($pricing->resolve(Product::findOrFail(3), 'web', 1)['mostrar_en_ofertas']);
    }

    public function test_offers_catalogs_include_all_categories_and_hide_them_when_disabled(): void
    {
        $id = $this->createPromotion();
        foreach (['/api/productos', '/api/app/productos'] as $endpoint) {
            $rows = $this->getJson($endpoint.'?ofertas=1&agencia_id=1')->assertOk()->json('data');
            $this->assertEqualsCanonicalizing([1, 2, 4], array_column($rows, 'id'));
            $filtered = $this->getJson($endpoint.'?ofertas=1&agencia_id=1&category_id=2')->assertOk()->json('data');
            $this->assertSame([2], array_column($filtered, 'id'));
        }
        // A manual product offer is independent of the promotion's visibility.
        DB::table('products')->where('id', 2)->update(['en_oferta' => true]);
        $this->putJson('/api/promotions/'.$id, $this->payload(['mostrar_en_ofertas' => false]))->assertOk();
        foreach (['/api/productos', '/api/app/productos'] as $endpoint) {
            $rows = $this->getJson($endpoint.'?ofertas=1&agencia_id=1')->assertOk()->json('data');
            $this->assertSame([2], array_column($rows, 'id'));
        }
        $this->assertEquals(80, app(PromotionPricingService::class)->resolve(Product::findOrFail(1), 'web', 1)['precio_venta']);
    }

    public function test_scope_changes_clear_old_selection_and_keep_largest_discount(): void
    {
        $specific = $this->createPromotion(['alcance' => 'PRODUCTOS', 'product_ids' => [1], 'porcentaje' => 30]);
        $general = $this->createPromotion(['alcance' => 'CATEGORIA', 'category_id' => 2]);
        $this->putJson('/api/promotions/'.$general, $this->payload())->assertOk()->assertJsonPath('category_id', null)->assertJsonPath('products', []);
        $pricing = app(PromotionPricingService::class);
        $this->assertSame($specific, $pricing->resolve(Product::findOrFail(1), 'web', 1)['promocion_id']);
        $this->assertEquals(70, $pricing->resolve(Product::findOrFail(1), 'web', 1)['precio_venta']);
        $this->putJson('/api/promotions/'.$specific, $this->payload())->assertOk()->assertJsonPath('products', []);
        $this->assertSame(0, DB::table('promotion_product')->where('promotion_id', $specific)->count());
    }

    public function test_all_categories_reject_zero_prices_but_ignore_controlled_products(): void
    {
        // The controlled product costs 0.10 but is not discounted.
        $this->createPromotion(['porcentaje' => 60]);
        DB::table('products')->where('id', 1)->update(['precio' => 0.1]);
        $this->postJson('/api/promotions', $this->payload(['porcentaje' => 60]))->assertUnprocessable()->assertJsonValidationErrors('porcentaje');
    }

    public function test_disabled_and_future_promotions_do_not_apply(): void
    {
        $id = $this->createPromotion(['activo' => false]);
        $this->assertEquals(100, app(PromotionPricingService::class)->resolve(Product::findOrFail(1), 'web', 1)['precio_venta']);
        $this->putJson('/api/promotions/'.$id, $this->payload([
            'permanente' => false, 'fecha_inicio' => now()->addDay()->format('Y-m-d H:i:s'),
            'fecha_fin' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ]))->assertOk();
        $this->assertEquals(100, app(PromotionPricingService::class)->resolve(Product::findOrFail(1), 'web', 1)['precio_venta']);
        $this->assertFalse(app(PromotionPricingService::class)->activeScopeIds('app', 1)['all_categories']);
    }

    public function test_header_announcements_are_available_to_branch_users_without_admin_access(): void
    {
        $global = $this->createPromotion(['mostrar_en_ofertas' => false]);
        $local = $this->createPromotion(['alcance' => 'CATEGORIA', 'category_id' => 2,
            'todas_agencias' => false, 'agencia_ids' => [1], 'porcentaje' => 25]);
        $this->createPromotion(['todas_agencias' => false, 'agencia_ids' => [2]]);
        $this->actingAs((new User())->forceFill(['id' => 9, 'agencia_id' => 1]), 'sanctum');

        // A different query-string branch cannot expose another branch's notices.
        $response = $this->getJson('/api/promotions/announcements?agencia_id=2')->assertOk();
        $this->assertSame([$local, $global], array_column($response->json('promociones'), 'id'));
        $response->assertJsonPath('promociones.0.categoria', 'Belleza');
        $response->assertJsonPath('promociones.1.alcance', 'TODAS_CATEGORIAS');
        $response->assertJsonPath('promociones.1.fin_ms', null);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertLessThan(2000, abs(microtime(true) * 1000 - $response->json('server_time_ms')));
        $this->getJson('/api/promotions')->assertForbidden();
    }

    public function test_header_hides_expired_paused_and_scheduled_promotions(): void
    {
        $id = $this->createPromotion(['alcance' => 'PRODUCTOS', 'product_ids' => [1, 2],
            'permanente' => false, 'fecha_inicio' => now()->subDay()->format('Y-m-d H:i:s'),
            'fecha_fin' => now()->addDay()->format('Y-m-d H:i:s')]);
        $this->createPromotion(['activo' => false]);
        $this->createPromotion(['permanente' => false,
            'fecha_inicio' => now()->subDays(2)->format('Y-m-d H:i:s'),
            'fecha_fin' => now()->subDay()->format('Y-m-d H:i:s')]);
        $this->createPromotion(['permanente' => false,
            'fecha_inicio' => now()->addDay()->format('Y-m-d H:i:s'),
            'fecha_fin' => now()->addDays(2)->format('Y-m-d H:i:s')]);
        $response = $this->getJson('/api/promotions/announcements?agencia_id=1')->assertOk();
        $this->assertSame([$id], array_column($response->json('promociones'), 'id'));
        $response->assertJsonPath('promociones.0.cantidad_productos', 2);
        $this->assertEqualsCanonicalizing(['Producto 1', 'Producto 2'], $response->json('promociones.0.productos'));
        $this->assertGreaterThan($response->json('server_time_ms'), $response->json('promociones.0.fin_ms'));

        $this->travel(2)->days();
        try {
            $ids = array_column($this->getJson('/api/promotions/announcements?agencia_id=1')->assertOk()->json('promociones'), 'id');
            $this->assertNotContains($id, $ids);
        } finally {
            $this->travelBack();
        }
    }
}
