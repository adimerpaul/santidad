<?php

namespace Tests\Feature;

use App\Http\Controllers\SalesController;
use App\Services\FacturacionSiatService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OnlineSaleCancellationTest extends TestCase
{
    public function test_cancellation_reopens_order_and_returns_stock_only_once(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('sales', function (Blueprint $t) {
            $t->id(); $t->string('estado'); $t->string('tipoVenta'); $t->integer('agencia_id');
            $t->json('online_orders')->nullable(); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id(); $t->integer('cantidad'); $t->integer('cantidadSucursal1'); $t->timestamps();
        });
        Schema::create('details', function (Blueprint $t) {
            $t->id(); $t->integer('sale_id'); $t->integer('product_id'); $t->integer('cantidad');
        });
        Schema::create('orders', function (Blueprint $t) {
            $t->id(); $t->string('order_number'); $t->string('source'); $t->string('status'); $t->json('meta'); $t->timestamps();
        });
        DB::table('sales')->insert(['id' => 1, 'estado' => 'ACTIVO', 'tipoVenta' => 'Ingreso', 'agencia_id' => 1]);
        DB::table('products')->insert(['id' => 1, 'cantidad' => 40, 'cantidadSucursal1' => 40]);
        DB::table('details')->insert(['sale_id' => 1, 'product_id' => 1, 'cantidad' => 10]);
        DB::table('orders')->insert(['id' => 1, 'order_number' => 'PEDIDOWEB_Nº1', 'source' => 'web', 'status' => 'sold', 'meta' => json_encode(['sale_id' => 1])]);
        $this->mock(FacturacionSiatService::class, function ($mock) {
            $mock->shouldReceive('anular')->once()->andReturnNull();
        });
        $controller = app(SalesController::class);
        $this->assertSame(200, $controller->salesAnular(1, new Request())->getStatusCode());
        $this->assertSame(50, DB::table('products')->value('cantidadSucursal1'));
        $this->assertSame('pending', DB::table('orders')->value('status'));
        $this->assertStringContainsString('PEDIDOWEB_N', DB::table('sales')->value('online_orders'));
        $this->assertSame(422, $controller->salesAnular(1, new Request())->getStatusCode());
        $this->assertSame(50, DB::table('products')->value('cantidadSucursal1'));
        DB::table('orders')->update(['status' => 'sold', 'meta' => json_encode(['sale_id' => 2])]);
        $this->assertSame(422, $controller->salesRevertir(1)->getStatusCode());
    }
}
