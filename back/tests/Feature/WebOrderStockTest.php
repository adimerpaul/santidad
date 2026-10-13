<?php

namespace Tests\Feature;

use App\Services\PromotionPricingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WebOrderStockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'web_stock_test', 'database.connections.web_stock_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('web_stock_test');
        Schema::create('agencias', function (Blueprint $table) {
            $table->id();
            $table->string('status');
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('activo');
            $table->integer('cantidadSucursal1');
            $table->integer('cantidadSucursal2');
        });
        (require database_path('migrations/2025_10_18_152547_create_orders_table.php'))->up();
        (require database_path('migrations/2025_10_18_152820_create_order_items_table.php'))->up();
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('calculated_total', 12, 2)->default(0);
            $table->decimal('rounding_adjustment', 12, 2)->default(0);
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('original_price', 12, 2)->nullable();
            $table->unsignedInteger('promotion_id')->nullable();
            $table->string('promotion_name')->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0);
        });
        DB::table('agencias')->insert([['id' => 1, 'status' => 'ACTIVO'], ['id' => 2, 'status' => 'ACTIVO']]);
        DB::table('products')->insert([
            ['id' => 1, 'nombre' => 'Producto uno', 'activo' => 'ACTIVO', 'cantidadSucursal1' => 0, 'cantidadSucursal2' => 5],
            ['id' => 2, 'nombre' => 'Producto dos', 'activo' => 'ACTIVO', 'cantidadSucursal1' => 10, 'cantidadSucursal2' => 2],
        ]);
        $this->mock(PromotionPricingService::class, function ($mock) {
            $mock->shouldReceive('resolve')->andReturn([
                'precio_original' => 10, 'precio_venta' => 10, 'porcentaje' => 0,
                'promocion_id' => null, 'promocion_nombre' => null,
            ]);
        });
    }

    private function payload(int $sucursal, array $products): array
    {
        return ['source' => 'web', 'sucursal_id' => $sucursal, 'items' => array_map(fn ($p) => [
            'product_id' => $p[0], 'nombre' => 'Producto', 'precio' => 10, 'cantidad' => $p[1],
        ], $products)];
    }

    public function test_stock_from_another_branch_does_not_allow_pickup(): void
    {
        $this->postJson('/api/orders', $this->payload(1, [[1, 1]]))
            ->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_partial_and_insufficient_stock_reject_the_entire_order(): void
    {
        $this->postJson('/api/orders', $this->payload(2, [[1, 2], [2, 3]]))
            ->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_duplicate_rows_cannot_bypass_stock_limit(): void
    {
        $this->postJson('/api/orders', $this->payload(2, [[1, 3], [1, 3]]))
            ->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_is_registered_when_all_quantities_fit_the_selected_branch(): void
    {
        $this->postJson('/api/orders', $this->payload(2, [[1, 5], [2, 2]]))
            ->assertCreated()->assertJsonCount(2, 'items')->assertJsonPath('items.0.quantity', 5);
        $this->assertDatabaseHas('orders', ['id' => 1, 'source' => 'web']);
        $this->assertDatabaseCount('order_items', 2);
    }

    public function test_inactive_branches_and_products_are_rejected(): void
    {
        DB::table('agencias')->where('id', 2)->update(['status' => 'INACTIVO']);
        $this->postJson('/api/orders', $this->payload(2, [[1, 1]]))
            ->assertUnprocessable()->assertJsonValidationErrors('sucursal_id');
        DB::table('products')->where('id', 2)->update(['activo' => 'INACTIVO']);
        $this->postJson('/api/orders', $this->payload(1, [[2, 1]]))
            ->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_delivery_without_pickup_branch_is_still_supported(): void
    {
        $data = $this->payload(2, [[1, 1]]);
        $data['sucursal_id'] = null;
        $this->postJson('/api/orders', $data)->assertCreated();
    }
}
