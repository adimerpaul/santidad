<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\FacturacionSiatService;
use App\Services\PromotionPricingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TraePricingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'trae_test', 'database.connections.trae_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'cache.default' => 'array']);
        DB::purge('trae_test');
        Cache::flush();
        foreach (['categories', 'subcategories'] as $name) {
            Schema::create($name, function (Blueprint $t) { $t->id(); $t->string('name'); });
        }
        Schema::create('agencias', function (Blueprint $t) {
            $t->id(); $t->string('nombre'); $t->string('status')->default('ACTIVO'); $t->integer('sucursal')->default(0);
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id(); $t->string('nombre'); $t->string('barra')->nullable(); $t->string('composicion')->nullable();
            $t->string('marca')->nullable(); $t->string('imagen')->nullable(); $t->string('activo')->default('ACTIVO');
            $t->decimal('precio', 10, 2); $t->decimal('costo', 10, 2)->default(0);
            $t->decimal('porcentaje', 5, 2)->default(8); $t->integer('category_id')->nullable();
            $t->integer('subcategory_id')->nullable(); $t->boolean('en_oferta')->default(false);
            $t->integer('cantidad')->default(10); $t->integer('cantidadSucursal1')->default(10);
            $t->integer('cantidadSucursal2')->default(0); $t->integer('cantidadAlmacen')->default(0); $t->timestamps();
        });
        Schema::create('buys', function (Blueprint $t) {
            $t->id(); $t->integer('product_id'); $t->string('lote'); $t->decimal('price');
            $t->date('dateExpiry'); $t->integer('cantidadVendida'); $t->timestamps(); $t->softDeletes();
        });
        foreach ([
            '2023_05_21_091135_create_clients_table.php', '2023_05_21_091136_create_sales_table.php',
            '2023_05_21_171231_create_details_table.php', '2025_10_18_152547_create_orders_table.php',
            '2025_10_18_152820_create_order_items_table.php', '2026_09_07_000000_add_rounding_fields_to_sales_and_orders.php',
            '2026_09_08_000002_create_promotions_tables.php', '2026_09_08_000003_add_promotion_snapshot_to_sale_details.php',
            '2026_09_08_000004_add_mostrar_en_ofertas_to_promotions_table.php', '2026_10_13_000001_add_online_orders_to_sales.php',
            '2026_10_29_000001_create_trae_pricing.php',
            '2026_10_29_000002_add_trae_sales_indexes.php',
            '2026_10_29_000003_add_validity_to_trae_discount_rules.php',
            '2026_10_29_000004_create_internal_combos.php',
            '2026_10_30_000001_add_price_source_to_trae_settings.php',
            '2026_10_30_000002_create_sale_requests.php',
            '2026_10_13_000003_create_factura_numeraciones_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::table('sales', function (Blueprint $t) {
            $t->decimal('descuento')->default(0); $t->decimal('descuento_producto')->default(0);
            $t->decimal('montoEfectivo')->nullable(); $t->decimal('montoQr')->nullable(); $t->string('qrId')->nullable();
            $t->softDeletes();
        });
        DB::table('categories')->insert([['id' => 1, 'name' => 'Salud'], ['id' => 2, 'name' => 'Belleza']]);
        DB::table('subcategories')->insert(['id' => 1, 'name' => 'Medicamentos Controlados']);
        DB::table('agencias')->insert([['id' => 1, 'nombre' => 'Matriz'], ['id' => 2, 'nombre' => 'Sucursal']]);
        DB::table('products')->insert([
            ['id' => 1, 'nombre' => 'Vitamina A', 'precio' => 100, 'category_id' => 1],
            ['id' => 2, 'nombre' => 'Vitamina B', 'precio' => 130, 'category_id' => 1],
            ['id' => 3, 'nombre' => 'Crema', 'precio' => 50, 'category_id' => 2],
        ]);
        $this->actingAs((new User())->forceFill(['id' => 1, 'name' => 'Admin']), 'sanctum');
        $this->mock(FacturacionSiatService::class, function ($mock) {
            $mock->shouldReceive('procesar')->andReturn(false);
            $mock->shouldReceive('anular')->andReturnNull();
            $mock->shouldReceive('revertirAnulacion')->andReturnNull();
        });
    }

    private function rule(array $target, float $percentage, bool $active = true)
    {
        return $this->postJson('/api/trae/rules', $target + ['porcentaje' => $percentage, 'activo' => $active])->assertOk();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function scheduled(array $target, float $percentage, string $start, string $end)
    {
        return $this->postJson('/api/trae/rules', $target + [
            'porcentaje' => $percentage, 'activo' => true, 'permanente' => false,
            'fecha_inicio' => $start, 'fecha_fin' => $end,
        ]);
    }

    private function sale(string $channel = 'trae', float $price = 100): array
    {
        return [
            'canal_venta' => $channel, 'agencia_id' => 1, 'metodoPago' => 'Efectivo',
            'client' => ['numeroDocumento' => '0', 'nombreRazonSocial' => 'SN', 'codigoTipoDocumentoIdentidad' => '1', 'email' => null],
            'products' => [['id' => 1, 'cantidadPedida' => 2, 'precioVenta' => $price]],
        ];
    }

    public function test_delivery_uses_its_own_prices_without_changing_web_app_or_physical_prices(): void
    {
        $this->getJson('/api/delivery/productos/1')->assertOk()->assertJsonPath('precio_venta', 100)
            ->assertJsonPath('descuento_porcentaje', 0)->assertJsonPath('disponibilidad.0.cantidad', 10);
        $this->rule(['category_id' => 1], 5);
        $delivery = $this->getJson('/api/delivery/productos/1')->assertOk()->assertJsonPath('precio_venta', 95)
            ->assertJsonPath('descuento_monto', 5);
        $this->assertStringContainsString('no-store', $delivery->headers->get('Cache-Control'));
        $this->assertSame(['id', 'nombre', 'imagen', 'precio', 'descuento_porcentaje', 'descuento_monto', 'precio_venta', 'disponibilidad'], array_keys($delivery->json()));
        $this->getJson('/api/delivery/productos?search=Vitamina&agencia_id=1&disponibles=1')->assertOk()->assertJsonPath('data.0.precio_venta', 95);
        $this->getJson('/api/productos/1?agencia_id=1')->assertOk()->assertJsonPath('precioVenta', 92);
        foreach (['physical', 'app', 'web'] as $channel) {
            $this->assertEquals(92, app(PromotionPricingService::class)->resolve(Product::findOrFail(1), $channel, 1)['precio_venta']);
        }
        $this->assertEquals(8, DB::table('products')->where('id', 1)->value('porcentaje'));
        $this->getJson('/api/productsSale?agencia=1&canal_venta=trae')->assertOk();
        $normal = collect($this->getJson('/api/productsSale?agencia=1')->assertOk()->json('products.data'))->firstWhere('id', 1);
        $trae = collect($this->getJson('/api/productsSale?agencia=1&canal_venta=trae')->assertOk()->json('products.data'))->firstWhere('id', 1);
        $this->assertEquals(92, $normal['precioVenta']);
        $this->assertEquals(95, $trae['precioVenta']);
    }

    public function test_product_zero_overrides_category_and_pausing_or_deleting_inherits_again(): void
    {
        $this->putJson('/api/trae/settings', ['descuento_general' => 2, 'comision_porcentaje' => 12])->assertOk();
        $this->rule(['category_id' => 1], 5);
        $this->rule(['product_ids' => [1]], 0);
        $this->getJson('/api/delivery/productos/1')->assertJsonPath('precio_venta', 100);
        $this->getJson('/api/delivery/productos/2')->assertJsonPath('precio_venta', 123.5);
        $this->rule(['product_ids' => [1]], 0, false);
        $this->getJson('/api/delivery/productos/1')->assertJsonPath('precio_venta', 95);
        $this->rule(['product_ids' => [1]], 3);
        $this->getJson('/api/delivery/productos/1')->assertJsonPath('precio_venta', 97);
        $id = DB::table('trae_discount_rules')->where('product_id', 1)->value('id');
        $this->deleteJson('/api/trae/rules/'.$id)->assertNoContent();
        $this->rule(['category_id' => 1], 5, false);
        $this->getJson('/api/delivery/productos/1')->assertJsonPath('precio_venta', 98);
        $this->assertDatabaseCount('trae_discount_rules', 1);
    }

    public function test_bulk_selection_only_changes_selected_products_and_category_covers_future_products(): void
    {
        $this->rule(['product_ids' => [1, 3]], 5);
        $this->getJson('/api/delivery/productos/2')->assertJsonPath('precio_venta', 130);
        $this->getJson('/api/delivery/productos/3')->assertJsonPath('precio_venta', 47.5);
        $this->rule(['category_id' => 1], 2);
        DB::table('products')->insert(['id' => 4, 'nombre' => 'Nuevo', 'precio' => 100, 'category_id' => 1]);
        $this->getJson('/api/delivery/productos/4')->assertJsonPath('precio_venta', 98);
        $this->getJson('/api/trae/products?search=Vitamina')->assertOk()->assertJsonPath('data.0.origen', 'PRODUCTO');
        DB::table('products')->where('id', 1)->update(['barra' => '123456789']);
        $this->getJson('/api/trae/products?search=123456789&per_page=1')->assertOk()->assertJsonPath('data.0.id', 1)
            ->assertJsonPath('data.0.barra', '123456789')->assertJsonPath('per_page', 1);
        $this->getJson('/api/trae/products?per_page=60')->assertOk()->assertJsonPath('per_page', 60);
        $this->getJson('/api/trae/products?per_page=61')->assertUnprocessable();
    }

    public function test_only_admin_can_read_or_change_configuration(): void
    {
        $this->actingAs((new User())->forceFill(['id' => 9, 'name' => 'Caja', 'agencia_id' => 1]), 'sanctum');
        $this->getJson('/api/trae')->assertForbidden();
        $this->getJson('/api/trae/products')->assertForbidden();
        $this->getJson('/api/trae/sales')->assertForbidden();
        $this->putJson('/api/trae/settings', ['descuento_general' => 5, 'comision_porcentaje' => 12])->assertForbidden();
        $this->postJson('/api/trae/rules', ['category_id' => 1, 'porcentaje' => 5, 'activo' => true])->assertForbidden();
        $this->deleteJson('/api/trae/rules/1')->assertForbidden();
        $this->postJson('/api/verificar-stock-venta', ['canal_venta' => 'trae', 'agencia_id' => 1, 'productos' => [['id' => 1, 'cantidadVenta' => 1]]])
            ->assertOk()->assertJsonPath('precios.0.precioVenta', 100);
    }

    public function test_invalid_rules_are_rejected_atomically_and_controlled_products_keep_exclusion(): void
    {
        foreach ([
            ['porcentaje' => -1], ['porcentaje' => 100], ['category_id' => 999],
            ['category_id' => 1, 'product_ids' => [1]], ['product_ids' => [1, 999]], ['product_ids' => [1, 1]],
        ] as $invalid) {
            $this->postJson('/api/trae/rules', $invalid + ['porcentaje' => 5, 'activo' => true])->assertUnprocessable();
        }
        $this->assertDatabaseCount('trae_discount_rules', 0);
        DB::table('products')->where('id', 1)->update(['subcategory_id' => 1]);
        $this->rule(['product_ids' => [1]], 5);
        $this->getJson('/api/delivery/productos/1')->assertJsonPath('descuento_porcentaje', 0);
        DB::table('products')->where('id', 2)->update(['precio' => 0.1]);
        $this->postJson('/api/trae/rules', ['category_id' => 1, 'porcentaje' => 90, 'activo' => true])->assertUnprocessable();
    }

    public function test_trae_sale_records_api_price_stock_origin_and_commission_without_changing_gross_payment(): void
    {
        $this->rule(['category_id' => 1], 5);
        $apiPrice = $this->getJson('/api/delivery/productos/1')->json('precio_venta');
        $response = $this->postJson('/api/sales', $this->sale('trae', $apiPrice))->assertOk()
            ->assertJsonPath('canal_venta', 'trae')->assertJsonPath('comision_trae_porcentaje', 12)
            ->assertJsonPath('comision_trae_monto', 22.8);
        $this->assertEquals(190, $response->json('montoTotal'));
        $this->assertEquals(190, $response->json('montoEfectivo'));
        $this->assertEquals(95, $response->json('details.0.precioUnitario'));
        $this->assertEquals(5, $response->json('details.0.promocion_porcentaje'));
        $this->assertEquals(8, DB::table('products')->where('id', 1)->value('cantidadSucursal1'));
        $this->assertEquals(8, DB::table('products')->where('id', 1)->value('cantidad'));
        $this->putJson('/api/trae/settings', ['descuento_general' => 0, 'comision_porcentaje' => 20])->assertOk();
        $this->assertEquals(22.8, DB::table('sales')->value('comision_trae_monto'));
        $this->putJson('/api/sales/'.$response->json('id'), ['details' => []])->assertUnprocessable();
    }

    public function test_trae_rejects_stale_prices_insufficient_stock_and_extra_discounts_without_partial_sales(): void
    {
        $this->rule(['category_id' => 1], 5);
        $this->postJson('/api/sales', $this->sale('trae', 100))->assertUnprocessable();
        foreach (['descuento', 'aporte'] as $field) {
            $this->postJson('/api/sales', $this->sale('trae', 95) + [$field => 1])->assertUnprocessable();
        }
        $payload = $this->sale('trae', 95);
        $payload['products'][0]['cantidadPedida'] = 11;
        $this->postJson('/api/sales', $payload)->assertUnprocessable();
        $payload['products'][0]['cantidadPedida'] = 1;
        $payload['products'][0]['online_order_number'] = 'WEB';
        DB::table('orders')->insert(['order_number' => 'WEB', 'status' => 'pending', 'source' => 'web']);
        $this->postJson('/api/sales', $payload)->assertUnprocessable();
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('details', 0);
        $this->assertEquals(10, DB::table('products')->where('id', 1)->value('cantidadSucursal1'));
        $this->getJson('/api/productsSale?canal_venta=otra')->assertUnprocessable();
    }

    public function test_normal_sales_work_even_without_trae_tables_and_keep_their_discount(): void
    {
        Schema::drop('trae_discount_rules');
        Schema::drop('trae_settings');
        $response = $this->postJson('/api/sales', $this->sale('sucursal', 92))->assertOk();
        $this->assertEquals(184, $response->json('montoTotal'));
        $this->assertEquals(0, $response->json('comision_trae_monto'));
        $this->assertSame('sucursal', $response->json('canal_venta'));
    }

    public function test_inactive_products_cannot_be_sold_in_trae_and_price_changes_are_seen_on_next_request(): void
    {
        $this->getJson('/api/delivery/productos/1')->assertJsonPath('precio_venta', 100);
        $this->rule(['category_id' => 1], 5);
        $this->getJson('/api/delivery/productos/1')->assertJsonPath('precio_venta', 95);
        DB::table('products')->where('id', 1)->update(['activo' => 'INACTIVO']);
        $this->getJson('/api/delivery/productos/1')->assertNotFound();
        $this->postJson('/api/sales', $this->sale('trae', 95))->assertUnprocessable();
        $this->postJson('/api/verificar-stock-venta', ['canal_venta' => 'trae', 'agencia_id' => 1, 'productos' => [['id' => 1, 'cantidadVenta' => 1]]])->assertStatus(400);
        $ids = array_column($this->getJson('/api/productsSale?canal_venta=trae')->assertOk()->json('products.data'), 'id');
        $this->assertNotContains(1, $ids);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_trae_cancellation_restores_stock_once_and_keeps_original_sale_and_commission(): void
    {
        $this->rule(['category_id' => 1], 5);
        $id = $this->postJson('/api/sales', $this->sale('trae', 95))->assertOk()->json('id');
        $this->getJson('/api/salesAnular/'.$id)->assertOk();
        $this->assertEquals(10, DB::table('products')->where('id', 1)->value('cantidadSucursal1'));
        $this->getJson('/api/salesAnular/'.$id)->assertUnprocessable();
        $this->getJson('/api/salesRevertir/'.$id)->assertOk();
        $this->assertEquals(8, DB::table('products')->where('id', 1)->value('cantidadSucursal1'));
        $this->assertEquals(190, DB::table('sales')->value('montoTotal'));
        $this->assertEquals(22.8, DB::table('sales')->value('comision_trae_monto'));
    }

    public function test_price_validation_respects_product_exceptions_and_checks_fallback_when_removing_them(): void
    {
        DB::table('products')->where('id', 2)->update(['precio' => 0.1]);
        $this->rule(['product_ids' => [2]], 0);
        // The tiny product is protected by its explicit zero rule.
        $this->rule(['category_id' => 1], 60);
        $this->getJson('/api/delivery/productos/1')->assertJsonPath('precio_venta', 40);
        $this->getJson('/api/delivery/productos/2')->assertJsonPath('precio_venta', 0.1);
        $this->putJson('/api/trae/settings', ['descuento_general' => 70, 'comision_porcentaje' => 12])->assertOk();
        $id = DB::table('trae_discount_rules')->where('product_id', 2)->value('id');
        $this->deleteJson('/api/trae/rules/'.$id)->assertUnprocessable();
        $this->postJson('/api/trae/rules', ['product_ids' => [2], 'porcentaje' => 0, 'activo' => false])->assertUnprocessable();
        $this->assertEquals(1, DB::table('trae_discount_rules')->where('id', $id)->value('activo'));
        $this->getJson('/api/delivery/productos/2')->assertJsonPath('precio_venta', 0.1);
        $this->rule(['category_id' => 1], 5);
        $this->deleteJson('/api/trae/rules/'.$id)->assertNoContent();
    }

    public function test_sales_report_only_sums_valid_trae_sales_and_preserves_historical_commissions(): void
    {
        $base = ['canal_venta' => 'trae', 'montoTotal' => 100, 'comision_trae_porcentaje' => 12, 'comision_trae_monto' => 12];
        foreach ([
            $base + ['id' => 1, 'agencia_id' => 1, 'fechaEmision' => '2026-10-01 00:00:00'],
            ['id' => 2, 'agencia_id' => 2, 'fechaEmision' => '2026-10-31 23:59:59', 'canal_venta' => 'trae', 'montoTotal' => 200, 'comision_trae_porcentaje' => 10, 'comision_trae_monto' => 20],
            $base + ['id' => 3, 'agencia_id' => 1, 'fechaEmision' => '2026-11-01 00:00:00'],
            $base + ['id' => 4, 'estado' => 'ANULADO', 'fechaEmision' => '2026-10-20 12:00:00'],
            $base + ['id' => 5, 'deleted_at' => now(), 'fechaEmision' => '2026-10-20 12:00:00'],
            ['id' => 6, 'canal_venta' => 'sucursal', 'montoTotal' => 900, 'fechaEmision' => '2026-10-20 12:00:00'],
        ] as $row) {
            DB::table('sales')->insert($row);
        }
        // Current commission must not recalculate past sales.
        DB::table('trae_settings')->where('id', 1)->update(['comision_porcentaje' => 25]);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $report = $this->getJson('/api/trae/sales?desde=2026-10-01&hasta=2026-10-31&per_page=1')->assertOk()
            ->assertJsonPath('resumen.cantidad', 2)->assertJsonPath('resumen.ventas', 300)
            ->assertJsonPath('resumen.comision', 32)->assertJsonPath('resumen.neto', 268)
            ->assertJsonPath('ventas.total', 2)->assertJsonPath('ventas.data.0.id', 2)
            ->assertJsonPath('ventas.data.0.neto', 180)->assertJsonPath('ventas.data.0.comision_trae_porcentaje', 10);
        $salesQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains(strtolower($q['query']), 'from "sales"'));
        $this->assertCount(2, $salesQueries, 'Only the combined summary and the paged rows should query sales.');
        $this->assertCount(1, $salesQueries->filter(fn ($q) => str_contains(strtolower($q['query']), 'count(')));
        DB::disableQueryLog();
        $this->assertStringContainsString('no-store', $report->headers->get('Cache-Control'));
        $this->getJson('/api/trae/sales?desde=2026-10-01&hasta=2026-10-31&agencia_id=1')->assertOk()
            ->assertJsonPath('resumen.cantidad', 1)->assertJsonPath('resumen.neto', 88)
            ->assertJsonPath('ventas.data.0.agencia.nombre', 'Matriz');
        $this->getJson('/api/trae/sales')->assertOk()->assertJsonPath('resumen.cantidad', 3)->assertJsonPath('resumen.neto', 356);
        $this->getJson('/api/trae/sales?desde=2026-01-01&hasta=2026-01-01')->assertOk()
            ->assertJsonPath('resumen.cantidad', 0)->assertJsonPath('resumen.neto', 0);
        $this->getJson('/api/trae/sales?desde=2026-10-31&hasta=2026-10-01')->assertUnprocessable();
        $this->getJson('/api/trae/sales?desde=fecha-invalida')->assertUnprocessable();
        $this->getJson('/api/trae/sales?agencia_id=999')->assertUnprocessable();
    }

    public function test_scheduled_category_and_product_exception_follow_exact_local_time_boundaries(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(9, 0));
        $this->putJson('/api/trae/settings', ['descuento_general' => 2, 'comision_porcentaje' => 12])->assertOk();
        $this->scheduled(['category_id' => 1], 5, '2026-10-29 10:00:00', '2026-10-29 11:00:00')->assertOk();
        $this->scheduled(['product_ids' => [1]], 0, '2026-10-29 10:15:00', '2026-10-29 10:30:00')->assertOk();
        foreach (['09:59:59' => 98, '10:00:00' => 95, '10:14:59' => 95, '10:15:00' => 100, '10:29:59' => 100, '10:30:00' => 95, '10:59:59' => 95, '11:00:00' => 98] as $time => $price) {
            $this->travelTo(\Carbon\Carbon::parse('2026-10-29 '.$time, 'America/La_Paz'));
            $this->getJson('/api/delivery/productos/1')->assertOk()->assertJsonPath('precio_venta', $price);
        }
        $this->postJson('/api/verificar-stock-venta', ['canal_venta' => 'trae', 'agencia_id' => 1, 'productos' => [['id' => 1, 'cantidadVenta' => 1]]])
            ->assertOk()->assertJsonPath('precios.0.precioVenta', 98);
        $this->getJson('/api/productos/1?agencia_id=1')->assertOk()->assertJsonPath('precioVenta', 92);
        $rule = $this->getJson('/api/trae')->assertOk()->json('rules.0');
        $this->assertEquals(\Carbon\Carbon::parse('2026-10-29 10:15:00', 'America/La_Paz')->getTimestamp() * 1000, $rule['inicio_ms']);
    }

    public function test_bulk_validity_applies_to_selected_products_and_legacy_pause_preserves_dates(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-29 09:00:00', 'America/La_Paz'));
        $this->scheduled(['product_ids' => [1, 3]], 5, '2026-10-29 10:00:00', '2026-10-29 11:00:00')->assertOk();
        $this->getJson('/api/delivery/productos/1')->assertJsonPath('precio_venta', 100);
        $this->rule(['product_ids' => [1]], 5, false);
        $this->assertDatabaseHas('trae_discount_rules', ['product_id' => 1, 'permanente' => 0, 'fecha_fin' => '2026-10-29 11:00:00']);
        $this->rule(['product_ids' => [1]], 5, true);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-29 10:00:00', 'America/La_Paz'));
        $this->getJson('/api/delivery/productos/1')->assertJsonPath('precio_venta', 95);
        $this->getJson('/api/delivery/productos/3')->assertJsonPath('precio_venta', 47.5);
        $this->getJson('/api/delivery/productos/2')->assertJsonPath('precio_venta', 130);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-29 11:00:00', 'America/La_Paz'));
        $this->getJson('/api/delivery/productos/1')->assertJsonPath('precio_venta', 100);
        $this->getJson('/api/delivery/productos/3')->assertJsonPath('precio_venta', 50);
        $this->postJson('/api/trae/rules', ['product_ids' => [1], 'porcentaje' => 5, 'activo' => true, 'permanente' => true])->assertOk();
        $this->assertDatabaseHas('trae_discount_rules', ['product_id' => 1, 'permanente' => 1, 'fecha_inicio' => null, 'fecha_fin' => null]);
        $this->getJson('/api/delivery/productos/1')->assertJsonPath('precio_venta', 95);
    }

    public function test_invalid_or_incomplete_validity_does_not_save_any_rules(): void
    {
        $base = ['product_ids' => [1, 2], 'porcentaje' => 5, 'activo' => true, 'permanente' => false];
        foreach ([
            [], ['fecha_inicio' => '2026-10-29 10:00:00'],
            ['fecha_inicio' => '2026-10-29 10:00:00', 'fecha_fin' => '2026-10-29 10:00:00'],
            ['fecha_inicio' => '2026-10-29 10:00:00', 'fecha_fin' => '2026-10-28 10:00:00'],
            ['fecha_inicio' => 'invalida', 'fecha_fin' => '2026-10-29 11:00:00'],
        ] as $invalid) {
            $this->postJson('/api/trae/rules', $base + $invalid)->assertUnprocessable();
        }
        unset($base['permanente']);
        $this->postJson('/api/trae/rules', $base + ['fecha_inicio' => '2026-10-29 10:00:00'])->assertUnprocessable();
        $this->assertDatabaseCount('trae_discount_rules', 0);
    }

    public function test_future_zero_prices_and_unsafe_expiry_are_rejected_before_scheduling(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-29 08:00:00', 'America/La_Paz'));
        DB::table('products')->where('id', 2)->update(['precio' => 0.1]);
        $this->scheduled(['product_ids' => [1, 2]], 90, '2026-10-29 10:00:00', '2026-10-29 12:00:00')->assertUnprocessable();
        $this->assertDatabaseCount('trae_discount_rules', 0);
        $this->rule(['product_ids' => [2]], 0);
        $this->scheduled(['category_id' => 1], 90, '2026-10-29 10:00:00', '2026-10-29 12:00:00')->assertOk();
        // Expiring the protective zero rule at 11:00 would expose the invalid category price.
        $this->scheduled(['product_ids' => [2]], 0, '2026-10-29 08:00:00', '2026-10-29 11:00:00')->assertUnprocessable();
        $this->assertDatabaseHas('trae_discount_rules', ['product_id' => 2, 'permanente' => 1]);
    }

    public function test_expiry_revalidates_checkout_without_changing_historical_sales(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-29 10:00:00', 'America/La_Paz'));
        $this->scheduled(['category_id' => 1], 5, '2026-10-29 09:00:00', '2026-10-29 11:00:00')->assertOk();
        $id = $this->postJson('/api/sales', $this->sale('trae', 95))->assertOk()->json('id');
        $this->travelTo(\Carbon\Carbon::parse('2026-10-29 11:00:00', 'America/La_Paz'));
        $this->postJson('/api/sales', $this->sale('trae', 95))->assertUnprocessable();
        $this->assertEquals(190, DB::table('sales')->where('id', $id)->value('montoTotal'));
        $this->assertEquals(22.8, DB::table('sales')->where('id', $id)->value('comision_trae_monto'));
        $this->assertDatabaseCount('sales', 1);
        $this->assertEquals(8, DB::table('products')->where('id', 1)->value('cantidadSucursal1'));
        $this->postJson('/api/sales', $this->sale('trae', 100))->assertOk()->assertJsonPath('montoTotal', 200);
    }

    private function comboData(array $override = []): array
    {
        return array_replace(['nombre' => 'COMBO CONVITE', 'modo' => 'conjunto', 'porcentaje' => 10, 'activo' => true, 'permanente' => true,
            'items' => [['product_id' => 1, 'cantidad' => 1], ['product_id' => 3, 'cantidad' => 1]]], $override);
    }

    private function comboLines(array $combo, int $count = 1, ?string $group = null): array
    {
        $group ??= (string) \Illuminate\Support\Str::uuid();
        return array_map(fn ($item) => ['id' => $item['product_id'], 'cantidadPedida' => $item['cantidad'] * $count,
            'precioVenta' => $item['precio_unitario'], 'combo_id' => $combo['id'], 'combo_grupo' => $group, 'combo_cantidad' => $count, 'combo_version' => $combo['version']], $combo['items']);
    }

    public function test_internal_combos_keep_public_delivery_prices_unchanged_and_total_allocation_exact(): void
    {
        $this->rule(['category_id' => 1], 5);
        $before = $this->getJson('/api/delivery/productos/1')->json();
        $data = $this->comboData(['modo' => 'total', 'porcentaje' => 0, 'precio_objetivo' => 100]);
        $preview = $this->postJson('/api/internal-combos/preview', $data)->assertOk()->json('items');
        $this->assertEquals(100, collect($preview)->sum(fn ($p) => $p['precio_unitario'] * $p['cantidad']));
        $this->assertDatabaseCount('internal_combos', 0);
        $created = $this->postJson('/api/internal-combos', $data)->assertCreated()->assertJsonPath('precio_total', 100)->assertJsonPath('canal', 'trae')->json();
        $this->assertEquals($before, $this->getJson('/api/delivery/productos/1')->json());
        $this->assertDatabaseCount('trae_discount_rules', 1);
        $this->getJson('/api/internal-combos')->assertOk()->assertJsonPath('data.0.nombre', 'COMBO CONVITE');
        $this->postJson('/api/internal-combos/'.$created['id'].'/load', ['agencia_id' => 1, 'canal_venta' => 'trae', 'cantidad' => 1])->assertOk()->assertJsonPath('precio_total', 100);
    }

    public function test_fixed_total_supports_mixed_quantities_and_rejects_unrepresentable_or_invalid_prices(): void
    {
        $mixed = $this->comboData(['modo' => 'total', 'precio_objetivo' => 200.1, 'items' => [['product_id' => 1, 'cantidad' => 2], ['product_id' => 3, 'cantidad' => 3]]]);
        $result = $this->postJson('/api/internal-combos', $mixed)->assertCreated()->assertJsonPath('precio_total', 200.1)->json();
        foreach ($result['items'] as $item) $this->assertEquals(0, \App\Support\Money::toCents($item['precio_unitario']) % 10);
        $this->postJson('/api/internal-combos', $this->comboData(['modo' => 'total', 'precio_objetivo' => 100, 'items' => [['product_id' => 1, 'cantidad' => 3]]]))->assertUnprocessable();
        $this->postJson('/api/internal-combos', $this->comboData(['modo' => 'total', 'precio_objetivo' => 2000]))->assertUnprocessable();
        $this->postJson('/api/internal-combos', $this->comboData(['modo' => 'producto', 'items' => [['product_id' => 1, 'cantidad' => 1, 'precio_unitario' => 0]]]))->assertUnprocessable();
        $this->postJson('/api/internal-combos', $this->comboData(['modo' => 'producto', 'items' => [['product_id' => 1, 'cantidad' => 1, 'precio_unitario' => 101]]]))->assertUnprocessable();
    }

    public function test_combo_checkout_keeps_each_group_price_and_aggregates_stock_with_loose_items(): void
    {
        $first = $this->postJson('/api/internal-combos', $this->comboData())->assertCreated()->assertJsonPath('precio_total', 135)->json();
        $second = $this->postJson('/api/internal-combos', $this->comboData(['nombre' => 'Otro combo', 'porcentaje' => 20, 'items' => [['product_id' => 1, 'cantidad' => 1]]]))->assertCreated()->json();
        $payload = $this->sale();
        $payload['products'] = array_merge($this->comboLines($first), $this->comboLines($second), [['id' => 1, 'cantidadPedida' => 1, 'precioVenta' => 100]]);
        $response = $this->postJson('/api/sales', $payload);
        $this->assertSame(200, $response->status(), $response->getContent());
        $sale = $response->assertJsonPath('canal_venta', 'trae')->assertJsonPath('montoTotal', 315)->assertJsonPath('comision_trae_monto', 37.8)->json();
        $this->assertCount(2, $sale['internal_combos']);
        $this->assertDatabaseCount('details', 4);
        $this->assertDatabaseHas('details', ['combo_id' => $first['id'], 'combo_nombre' => 'COMBO CONVITE', 'precioUnitario' => 90]);
        $this->assertEquals(7, DB::table('products')->where('id', 1)->value('cantidadSucursal1'));
        $this->getJson('/api/salesAnular/'.$sale['id'])->assertOk();
        $this->assertEquals(10, DB::table('products')->where('id', 1)->value('cantidadSucursal1'));
        $this->deleteJson('/api/internal-combos/'.$first['id'])->assertNoContent();
        $this->getJson('/api/salesRevertir/'.$sale['id'])->assertOk();
        $this->assertEquals(7, DB::table('products')->where('id', 1)->value('cantidadSucursal1'));
        $this->assertEquals(315, DB::table('sales')->where('id', $sale['id'])->value('montoTotal'));
    }

    public function test_combo_tampering_partial_groups_stale_versions_and_combined_overselling_are_rejected_atomically(): void
    {
        $combo = $this->postJson('/api/internal-combos', $this->comboData())->assertCreated()->json();
        $base = $this->sale(); $base['products'] = $this->comboLines($combo);
        $partial = $base; array_pop($partial['products']);
        $this->postJson('/api/sales', $partial)->assertUnprocessable();
        $price = $base; $price['products'][0]['precioVenta'] = 1;
        $this->postJson('/api/sales', $price)->assertUnprocessable();
        $quantity = $base; $quantity['products'][0]['cantidadPedida'] = 2;
        $this->postJson('/api/sales', $quantity)->assertUnprocessable();
        $physical = $base; $physical['canal_venta'] = 'sucursal';
        $this->postJson('/api/sales', $physical)->assertUnprocessable();
        $extra = $base; $extra['descuento'] = 1;
        $this->postJson('/api/sales', $extra)->assertUnprocessable();
        $many = $base; $many['products'] = array_merge($this->comboLines($combo, 6), $this->comboLines($combo, 6));
        $this->postJson('/api/sales', $many)->assertUnprocessable();
        $verify = array_map(fn ($p) => $p + ['cantidadVenta' => $p['cantidadPedida']], $many['products']);
        $this->postJson('/api/verificar-stock-venta', ['canal_venta' => 'trae', 'agencia_id' => 1, 'productos' => $verify])->assertStatus(400);
        $this->putJson('/api/internal-combos/'.$combo['id'], $this->comboData(['version' => $combo['version'], 'porcentaje' => 5]))->assertOk();
        $this->postJson('/api/sales', $base)->assertUnprocessable();
        $this->assertDatabaseCount('sales', 0);
        $this->assertEquals(10, DB::table('products')->where('id', 1)->value('cantidadSucursal1'));
    }

    public function test_combo_auth_validity_and_controlled_exclusion_are_enforced(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-29 09:00:00', 'America/La_Paz'));
        DB::table('products')->where('id', 1)->update(['subcategory_id' => 1]);
        $combo = $this->postJson('/api/internal-combos', $this->comboData(['permanente' => false, 'fecha_inicio' => '2026-10-29 10:00:00', 'fecha_fin' => '2026-10-29 11:00:00']))->assertCreated()->assertJsonPath('precio_total', 145)->json();
        $this->getJson('/api/internal-combos/available?agencia_id=1&canal_venta=trae')->assertOk()->assertJsonPath('total', 0);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-29 10:00:00', 'America/La_Paz'));
        $this->actingAs((new User())->forceFill(['id' => 9, 'agencia_id' => 1]), 'sanctum');
        $this->getJson('/api/internal-combos')->assertForbidden();
        $this->postJson('/api/internal-combos', $this->comboData())->assertForbidden();
        $this->postJson('/api/internal-combos/preview', $this->comboData())->assertForbidden();
        $this->deleteJson('/api/internal-combos/'.$combo['id'])->assertForbidden();
        $this->getJson('/api/internal-combos/available?agencia_id=2&canal_venta=trae')->assertOk()->assertJsonPath('data.0.combos_disponibles', 10);
        $this->postJson('/api/internal-combos/'.$combo['id'].'/load', ['agencia_id' => 2, 'canal_venta' => 'trae', 'cantidad' => 1])->assertOk();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-29 11:00:00', 'America/La_Paz'));
        $this->postJson('/api/internal-combos/'.$combo['id'].'/load', ['agencia_id' => 1, 'canal_venta' => 'trae', 'cantidad' => 1])->assertUnprocessable();
    }

    public function test_reactivating_combo_sale_checks_aggregated_branch_and_total_stock_before_siat(): void
    {
        $combo = $this->postJson('/api/internal-combos', $this->comboData())->assertCreated()->json();
        $payload = $this->sale();
        $payload['products'] = array_merge($this->comboLines($combo), $this->comboLines($combo));
        $id = $this->postJson('/api/sales', $payload)->assertOk()->json('id');
        $this->getJson('/api/salesAnular/'.$id)->assertOk();
        $this->mock(FacturacionSiatService::class, function ($mock) {
            $mock->shouldNotReceive('revertirAnulacion');
        });
        // Each detail asks for one unit, but both groups need two in total.
        foreach ([['cantidad' => 10, 'cantidadSucursal1' => 1], ['cantidad' => 1, 'cantidadSucursal1' => 10]] as $stock) {
            DB::table('products')->where('id', 1)->update($stock);
            $this->getJson('/api/salesRevertir/'.$id)->assertUnprocessable()->assertJsonPath('message', 'Stock insuficiente para reactivar la venta. Revisa los productos y la sucursal.');
            $this->assertDatabaseHas('sales', ['id' => $id, 'estado' => 'ANULADO']);
            $this->assertDatabaseHas('products', ['id' => 1] + $stock);
            $this->assertEquals(10, DB::table('products')->where('id', 3)->value('cantidadSucursal1'));
        }
    }

    public function test_reclassified_controlled_product_cannot_keep_an_old_combo_discount(): void
    {
        $combo = $this->postJson('/api/internal-combos', $this->comboData())->assertCreated()->json();
        DB::table('products')->where('id', 1)->update(['subcategory_id' => 1]);
        $this->getJson('/api/internal-combos/available?agencia_id=1&canal_venta=trae')->assertOk()->assertJsonPath('data.0.combos_disponibles', 0);
        $this->postJson('/api/internal-combos/'.$combo['id'].'/load', ['agencia_id' => 1, 'canal_venta' => 'trae', 'cantidad' => 1])->assertUnprocessable();
        $lines = $this->comboLines($combo);
        $this->postJson('/api/verificar-stock-venta', ['canal_venta' => 'trae', 'agencia_id' => 1,
            'productos' => array_map(fn ($p) => $p + ['cantidadVenta' => $p['cantidadPedida']], $lines)])->assertStatus(400);
        $payload = $this->sale(); $payload['products'] = $lines;
        $this->postJson('/api/sales', $payload)->assertUnprocessable();
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('details', 0);
        $this->assertDatabaseHas('products', ['id' => 1, 'cantidad' => 10, 'cantidadSucursal1' => 10]);
        // Editing the combo recalculates the excluded product at its full price.
        $this->putJson('/api/internal-combos/'.$combo['id'], $this->comboData(['version' => $combo['version']]))
            ->assertOk()->assertJsonPath('precio_total', 145);
    }

    public function test_stock_preview_also_rejects_insufficient_total_inventory(): void
    {
        DB::table('products')->where('id', 1)->update(['cantidad' => 1, 'cantidadSucursal1' => 10]);
        $this->postJson('/api/verificar-stock-venta', ['canal_venta' => 'trae', 'agencia_id' => 1,
            'productos' => [['id' => 1, 'cantidadVenta' => 2]]])->assertStatus(400);
        $this->assertDatabaseCount('sales', 0);
    }

    private function priceSource(string $source, ?int $agency = null)
    {
        return $this->putJson('/api/trae/settings', ['descuento_general' => 2, 'comision_porcentaje' => 12,
            'fuente_precios' => $source, 'fuente_agencia_id' => $agency]);
    }

    private function sourcePromotion(string $channel, float $percentage, ?int $agency = null): \App\Models\Promotion
    {
        $promotion = \App\Models\Promotion::create([
            'nombre' => 'Promoción '.$channel, 'alcance' => 'CATEGORIA', 'category_id' => 1, 'porcentaje' => $percentage,
            'activo' => true, 'permanente' => true, 'todas_agencias' => $agency === null,
            'canal_web' => $channel === 'web', 'canal_app' => $channel === 'app', 'canal_fisico' => $channel === 'physical',
        ]);
        if ($agency) $promotion->agencias()->attach($agency);
        app(PromotionPricingService::class)->clearCache();
        return $promotion;
    }

    public function test_trae_can_follow_each_existing_channel_in_api_catalog_and_checkout_without_stacking(): void
    {
        $this->rule(['product_ids' => [1]], 50);
        $this->sourcePromotion('web', 15);
        $this->sourcePromotion('app', 20);
        $this->sourcePromotion('physical', 25, 1);
        foreach (['web' => 85, 'app' => 80, 'physical' => 75] as $source => $price) {
            $this->priceSource($source, $source === 'physical' ? 1 : null)->assertOk()->assertJsonPath('settings.fuente_precios', $source);
            $this->getJson('/api/delivery/productos/1')->assertOk()->assertJsonPath('precio_venta', $price)->assertJsonPath('precio', 100);
            $preview = collect($this->getJson('/api/trae/products')->assertOk()->json('data'));
            $this->assertEquals($price, $preview->firstWhere('id', 1)['precio_venta']);
            // Product 3 has no managed promotion: its ordinary permanent 8% still applies.
            $this->assertEquals(46, $preview->firstWhere('id', 3)['precio_venta']);
            $this->postJson('/api/verificar-stock-venta', ['canal_venta' => 'trae', 'agencia_id' => 1, 'productos' => [['id' => 1, 'cantidadVenta' => 2]]])
                ->assertOk()->assertJsonPath('precios.0.precioVenta', $price);
            $this->postJson('/api/sales', $this->sale('trae', $price))->assertOk()->assertJsonPath('montoTotal', $price * 2)->assertJsonPath('comision_trae_monto', fn ($value) => abs($value - $price * 2 * 0.12) < 0.00001);
        }
        $this->assertDatabaseCount('trae_discount_rules', 1);
        $this->assertDatabaseHas('trae_discount_rules', ['product_id' => 1, 'porcentaje' => 50]);
        $this->priceSource('propio')->assertOk();
        $this->getJson('/api/delivery/productos/1')->assertJsonPath('precio_venta', 50);
        $this->getJson('/api/delivery/productos/3')->assertJsonPath('precio_venta', 49);
        $this->getJson('/api/productos/1?agencia_id=1')->assertJsonPath('precioVenta', 85);
        $this->assertEquals(75, app(PromotionPricingService::class)->resolve(Product::findOrFail(1), 'physical', 1)['precio_venta']);
        $this->assertEquals(80, app(PromotionPricingService::class)->resolve(Product::findOrFail(1), 'app', 1)['precio_venta']);
    }

    public function test_reference_branch_is_explicit_and_gives_one_price_independent_of_stock_filter_or_cash_branch(): void
    {
        $this->sourcePromotion('web', 10);
        $this->sourcePromotion('web', 25, 1);
        $this->sourcePromotion('web', 40, 2);
        $this->priceSource('web')->assertOk();
        $this->getJson('/api/delivery/productos/1')->assertJsonPath('precio_venta', 90);
        $this->priceSource('web', 2)->assertOk();
        foreach (['', '?agencia_id=1', '?agencia_id=2'] as $query) {
            $this->getJson('/api/delivery/productos/1'.$query)->assertOk()->assertJsonPath('precio_venta', 60)
                ->assertJsonPath('disponibilidad.0.cantidad', 10)->assertJsonPath('disponibilidad.1.cantidad', 0);
        }
        $list = collect($this->getJson('/api/delivery/productos?agencia_id=1')->assertOk()->json('data'));
        $this->assertEquals(60, $list->firstWhere('id', 1)['precio_venta']);
        $this->postJson('/api/sales', $this->sale('trae', 60))->assertOk()->assertJsonPath('agencia_id', 1)->assertJsonPath('montoTotal', 120);
        $this->sourcePromotion('physical', 35, 2);
        $this->priceSource('physical', 2)->assertOk();
        $this->getJson('/api/delivery/productos/1?agencia_id=1')->assertJsonPath('precio_venta', 65);
    }

    public function test_source_changes_validate_branch_keep_legacy_updates_and_preserve_combo_prices(): void
    {
        $combo = $this->postJson('/api/internal-combos', $this->comboData())->assertCreated()->json();
        $this->priceSource('physical')->assertUnprocessable();
        $this->priceSource('unknown')->assertUnprocessable();
        $this->priceSource('web', 999)->assertUnprocessable();
        $this->getJson('/api/trae')->assertJsonPath('settings.fuente_precios', 'propio');
        $this->priceSource('physical', 1)->assertOk();
        // An older client editing only commission cannot reset the chosen source.
        $this->putJson('/api/trae/settings', ['descuento_general' => 3, 'comision_porcentaje' => 10])->assertOk()
            ->assertJsonPath('settings.fuente_precios', 'physical')->assertJsonPath('settings.fuente_agencia_id', 1);
        $payload = $this->sale(); $payload['products'] = $this->comboLines($combo);
        $this->postJson('/api/sales', $payload)->assertOk()->assertJsonPath('montoTotal', 135)->assertJsonPath('comision_trae_monto', 13.5);
        $this->priceSource('propio', 1)->assertOk()->assertJsonPath('settings.fuente_agencia_id', null);
    }

    public function test_followed_promotions_update_and_expire_automatically_with_stale_checkout_rejected(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-30 10:00:00', 'America/La_Paz'));
        $promo = $this->sourcePromotion('web', 15);
        $promo->update(['permanente' => false, 'fecha_fin' => '2026-10-30 11:00:00']);
        app(PromotionPricingService::class)->clearCache();
        $this->priceSource('web')->assertOk();
        $this->getJson('/api/delivery/productos/1')->assertJsonPath('precio_venta', 85);
        $promo->update(['porcentaje' => 20]);
        app(PromotionPricingService::class)->clearCache();
        $this->getJson('/api/delivery/productos/1')->assertJsonPath('precio_venta', 80);
        $this->postJson('/api/sales', $this->sale('trae', 85))->assertUnprocessable();
        $id = $this->postJson('/api/sales', $this->sale('trae', 80))->assertOk()->json('id');
        $this->travelTo(\Carbon\Carbon::parse('2026-10-30 11:00:01', 'America/La_Paz'));
        $this->getJson('/api/delivery/productos/1')->assertJsonPath('precio_venta', 92);
        $this->postJson('/api/sales', $this->sale('trae', 80))->assertUnprocessable();
        $this->assertDatabaseHas('sales', ['id' => $id, 'montoTotal' => 160, 'comision_trae_monto' => 19.2]);
        $this->assertDatabaseCount('sales', 1);
    }

    public function test_following_a_channel_reuses_pricing_queries_for_the_whole_catalog(): void
    {
        $this->sourcePromotion('app', 20);
        $this->priceSource('app')->assertOk();
        app(PromotionPricingService::class)->clearCache();
        DB::enableQueryLog(); DB::flushQueryLog();
        $this->getJson('/api/delivery/productos')->assertOk();
        $queries = collect(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertCount(0, $queries->filter(fn ($q) => str_contains($q['query'], 'trae_discount_rules')));
        $this->assertCount(1, $queries->filter(fn ($q) => str_contains($q['query'], 'from "promotions"')));
        $this->assertCount(1, $queries->filter(fn ($q) => str_contains($q['query'], 'from "subcategories"')));
    }

    public function test_stale_settings_cannot_overwrite_a_new_source_chosen_in_another_session(): void
    {
        $old = $this->getJson('/api/trae')->assertOk()->json('settings');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $old['revision'] ?? '');
        $this->priceSource('app')->assertOk();
        $this->putJson('/api/trae/settings', [
            'descuento_general' => $old['descuento_general'], 'comision_porcentaje' => 10,
            'fuente_precios' => $old['fuente_precios'], 'fuente_agencia_id' => $old['fuente_agencia_id'], 'revision' => $old['revision'],
        ])->assertConflict();
        $current = $this->getJson('/api/trae')->assertOk()->assertJsonPath('settings.fuente_precios', 'app')->assertJsonPath('settings.comision_porcentaje', 12)->json('settings');
        $this->putJson('/api/trae/settings', [
            'descuento_general' => 2, 'comision_porcentaje' => 10, 'fuente_precios' => 'app', 'fuente_agencia_id' => null, 'revision' => $current['revision'],
        ])->assertOk()->assertJsonPath('settings.comision_porcentaje', 10);
    }

    public function test_commission_only_update_does_not_rescan_catalog_or_rewrite_source(): void
    {
        $this->priceSource('app')->assertOk();
        DB::enableQueryLog(); DB::flushQueryLog();
        $this->putJson('/api/trae/settings', ['descuento_general' => 2, 'comision_porcentaje' => 10])->assertOk()
            ->assertJsonPath('settings.fuente_precios', 'app')->assertJsonPath('settings.comision_porcentaje', 10);
        $queries = collect(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertCount(0, $queries->filter(fn ($q) => str_contains($q['query'], 'from "products"')));
    }

    private function invoicePayload(): array
    {
        DB::table('agencias')->where('id', 1)->update(['sucursal' => 1]);
        $payload = $this->sale('sucursal', 92);
        $payload['request_id'] = (string) \Illuminate\Support\Str::uuid();
        $payload['client']['numeroDocumento'] = '1234567';
        $payload['client']['nombreRazonSocial'] = 'Cliente de prueba';
        return $payload;
    }

    private function invoiceMock(callable $process): void
    {
        $this->mock(FacturacionSiatService::class, function ($mock) use ($process) {
            $mock->shouldReceive('asegurarCufdVigente')->once()->andReturn((new \App\Models\Cufd())->forceFill(['id' => 1]));
            $mock->shouldReceive('procesar')->once()->andReturnUsing($process);
            $mock->shouldReceive('anular')->andReturnNull();
        });
    }

    public function test_repeated_invoice_request_records_one_sale_stock_movement_and_invoice_number(): void
    {
        $payload = $this->invoicePayload();
        $this->invoiceMock(function ($sale) { $sale->venta = 'F'; $sale->cuf = 'TEST-CUF'; $sale->save(); return true; });
        $first = $this->postJson('/api/sales', $payload)->assertOk()->assertJsonPath('numeroFactura', 1)->assertJsonPath('venta', 'F')->json();
        $this->postJson('/api/sales', $payload)->assertOk()->assertJsonPath('id', $first['id'])->assertJsonPath('numeroFactura', 1)->assertJsonPath('solicitud_repetida', true);
        $this->getJson('/api/sale-requests/'.$payload['request_id'])->assertOk()->assertJsonPath('id', $first['id'])->assertJsonPath('procesamiento_pendiente', false);
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('details', 1);
        $this->assertDatabaseCount('sale_requests', 1);
        $this->assertDatabaseHas('products', ['id' => 1, 'cantidad' => 8, 'cantidadSucursal1' => 8]);
        $this->assertEquals(1, DB::table('factura_numeraciones')->value('ultimo_numero'));
    }

    public function test_retry_while_siat_is_still_processing_returns_the_committed_sale_without_emitting_again(): void
    {
        $payload = $this->invoicePayload();
        $this->invoiceMock(function ($sale) use ($payload) {
            $this->postJson('/api/sales', $payload)->assertOk()->assertJsonPath('id', $sale->id)->assertJsonPath('procesamiento_pendiente', true);
            $this->getJson('/api/sale-requests/'.$payload['request_id'])->assertOk()->assertJsonPath('id', $sale->id);
            $sale->venta = 'F'; $sale->cuf = 'TEST-CUF'; $sale->save();
            return true;
        });
        $this->postJson('/api/sales', $payload)->assertOk()->assertJsonPath('procesamiento_pendiente', false)->assertJsonPath('venta', 'F');
        $this->assertDatabaseCount('sales', 1);
        $this->assertEquals(1, DB::table('factura_numeraciones')->value('ultimo_numero'));
        $this->assertEquals(8, DB::table('products')->where('id', 1)->value('cantidadSucursal1'));
    }

    public function test_post_commit_invoice_failure_is_reported_as_registered_and_never_recreates_the_sale(): void
    {
        $payload = $this->invoicePayload();
        $this->invoiceMock(function () { throw new \RuntimeException('Simulated response loss after stock commit'); });
        $sale = $this->postJson('/api/sales', $payload)->assertOk()->assertJsonPath('procesamiento_pendiente', false)->json();
        $this->assertNotEmpty($sale['advertencia_procesamiento']);
        $this->postJson('/api/sales', $payload)->assertOk()->assertJsonPath('id', $sale['id'])->assertJsonPath('numeroFactura', 1);
        $this->assertDatabaseCount('sales', 1);
        $this->assertEquals(1, DB::table('factura_numeraciones')->value('ultimo_numero'));
        $this->assertEquals(8, DB::table('products')->where('id', 1)->value('cantidadSucursal1'));
    }

    public function test_request_ids_cannot_change_payload_cross_users_or_resell_an_annulled_sale(): void
    {
        $payload = $this->invoicePayload();
        $this->invoiceMock(function ($sale) { $sale->venta = 'F'; $sale->save(); return true; });
        $id = $this->postJson('/api/sales', $payload)->assertOk()->json('id');
        $changed = $payload; $changed['products'][0]['cantidadPedida'] = 1;
        $this->postJson('/api/sales', $changed)->assertConflict();
        $this->actingAs((new User())->forceFill(['id' => 2]), 'sanctum');
        $this->postJson('/api/sales', $payload)->assertForbidden();
        $this->getJson('/api/sale-requests/'.$payload['request_id'])->assertForbidden();
        $this->actingAs((new User())->forceFill(['id' => 1]), 'sanctum');
        $this->getJson('/api/salesAnular/'.$id)->assertOk();
        $this->postJson('/api/sales', $payload)->assertOk()->assertJsonPath('id', $id)->assertJsonPath('estado', 'ANULADO');
        $this->assertDatabaseCount('sales', 1);
        $this->assertEquals(10, DB::table('products')->where('id', 1)->value('cantidadSucursal1'));
    }

    public function test_failed_sale_rolls_back_its_request_and_distinct_attempts_can_sell_identical_products(): void
    {
        $payload = $this->sale('trae', 100);
        $payload['request_id'] = (string) \Illuminate\Support\Str::uuid();
        $invalid = $payload; $invalid['products'][0]['cantidadPedida'] = 100;
        $this->postJson('/api/sales', $invalid)->assertUnprocessable();
        $this->assertDatabaseCount('sale_requests', 0);
        $this->getJson('/api/sale-requests/'.$payload['request_id'])->assertNotFound();
        $this->postJson('/api/sales', $payload)->assertOk();
        $payload['request_id'] = (string) \Illuminate\Support\Str::uuid();
        $this->postJson('/api/sales', $payload)->assertOk();
        $this->assertDatabaseCount('sales', 2);
        $this->assertDatabaseCount('sale_requests', 2);
        $this->assertEquals(6, DB::table('products')->where('id', 1)->value('cantidadSucursal1'));
    }
}
