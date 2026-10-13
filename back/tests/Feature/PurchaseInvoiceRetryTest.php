<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PurchaseInvoiceRetryTest extends TestCase
{
    private const KEY = '11111111-1111-4111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'retry_test', 'database.connections.retry_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('retry_test');
        foreach (['users', 'agencias', 'clients', 'vendedores'] as $table) {
            Schema::create($table, fn (Blueprint $t) => $t->id());
        }
        DB::table('users')->insert(['id' => 1]);
        DB::table('agencias')->insert(['id' => 1]);
        Schema::create('products', function (Blueprint $t) {
            $t->id(); $t->integer('cantidad')->default(0); $t->integer('cantidadAlmacen')->default(0);
            $t->integer('cantidadSucursal1')->default(0); $t->decimal('precio', 10, 2)->nullable();
            $t->decimal('costo', 10, 2)->nullable(); $t->timestamps();
        });
        Schema::create('buys', function (Blueprint $t) {
            $t->id();
            foreach (['user_id', 'agencia_comprador_id', 'product_id', 'agencia_id', 'proveedor_id', 'vendedor_id', 'factura_id'] as $field) $t->integer($field)->nullable();
            foreach (['lote', 'dateExpiry', 'date', 'time', 'factura'] as $field) $t->string($field)->nullable();
            foreach (['quantity', 'cantidadVendida', 'price', 'total'] as $field) $t->decimal($field, 10, 2)->nullable();
            $t->timestamps(); $t->softDeletes();
        });
        (require database_path('migrations/2025_12_17_200840_create_facturas_table.php'))->up();
        (require database_path('migrations/2026_10_13_000002_create_purchase_submissions.php'))->up();
        DB::table('products')->insert(['id' => 1]);
        $this->actingAs((new User())->forceFill(['id' => 1, 'agencia_id' => 1]), 'sanctum');
    }

    private function purchase(): array
    {
        return ['request_id' => self::KEY, 'agencia_id' => 1, 'agencia_comprador_id' => 1,
            'buys' => [['id' => 1, 'lote' => 'TEST', 'fechaVencimiento' => '2027-12-01', 'cantidadCompra' => 10, 'price' => 2]]];
    }

    private function invoice(array $ids): array
    {
        return ['request_id' => self::KEY, 'detalle_compras' => $ids, 'fecha_compra' => '2026-10-13',
            'monto_total' => 20, 'tipo_pago' => 'Contado', 'metodo_pago' => 'Efectivo', 'agencia_id' => 1, 'proveedor_id' => 0];
    }

    public function test_repeated_purchase_and_invoice_requests_return_existing_results(): void
    {
        $ids = $this->postJson('/api/compraInsert', $this->purchase())->assertOk()->json('buy_ids');
        $this->postJson('/api/compraInsert', $this->purchase())->assertOk()->assertJsonPath('buy_ids', $ids);
        $this->assertEquals(10, DB::table('products')->value('cantidadSucursal1'));
        $this->assertSame(1, DB::table('buys')->count());
        $id = $this->postJson('/api/facturas', $this->invoice($ids))->assertCreated()->json('id');
        $this->postJson('/api/facturas', $this->invoice($ids))->assertCreated()->assertJsonPath('id', $id);
        $this->assertSame(1, DB::table('facturas')->count());
        $this->assertSame(1, DB::table('pagos_factura')->count());
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_failed_invoice_can_be_corrected_without_repeating_the_purchase(): void
    {
        $ids = $this->postJson('/api/compraInsert', $this->purchase())->assertOk()->json('buy_ids');
        $this->postJson('/api/facturas', array_replace($this->invoice($ids), ['tipo_pago' => 'INVALIDO']))->assertStatus(500);
        $this->assertSame(0, DB::table('facturas')->count());
        $this->postJson('/api/facturas', $this->invoice($ids))->assertCreated();
        $this->assertSame(1, DB::table('buys')->count());
        $this->assertEquals(10, DB::table('products')->value('cantidadSucursal1'));
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_completed_operation_cannot_be_reused_to_register_different_quantities(): void
    {
        $payload = $this->purchase();
        $this->postJson('/api/compraInsert', $payload)->assertOk();
        $payload['buys'][0]['cantidadCompra'] = 20;
        $this->postJson('/api/compraInsert', $payload)->assertUnprocessable();
        $this->assertEquals(10, DB::table('products')->value('cantidadSucursal1'));
    }

    public function test_rejected_purchase_does_not_consume_the_key_and_can_be_corrected(): void
    {
        $payload = $this->purchase();
        $payload['buys'][0]['cantidadCompra'] = 0;
        $this->postJson('/api/compraInsert', $payload)->assertStatus(400);
        $this->assertSame(0, DB::table('purchase_submissions')->count());
        $this->assertSame(0, DB::table('buys')->count());
        $this->postJson('/api/compraInsert', $this->purchase())->assertOk();
        $this->assertEquals(10, DB::table('products')->value('cantidadSucursal1'));
        $this->assertSame(0, DB::transactionLevel());
    }
}
