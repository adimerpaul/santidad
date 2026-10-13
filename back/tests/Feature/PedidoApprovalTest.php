<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PedidoApprovalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'pedido_test', 'database.connections.pedido_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('pedido_test');
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('estado');
            $table->timestamps();
        });
        Schema::create('pedido_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pedido_id');
            $table->unsignedInteger('product_id');
            $table->integer('cantidad');
            $table->timestamps();
        });
        Schema::create('pedido_modificaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pedido_id');
            $table->unsignedInteger('user_id');
            $table->string('accion');
            $table->string('estado_anterior');
            $table->string('estado_nuevo');
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
        Schema::create('pedido_modificacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('modificacion_id');
            $table->unsignedInteger('pedido_detail_id');
            $table->integer('cantidad_anterior');
            $table->integer('cantidad_nueva');
            $table->string('accion');
            $table->timestamps();
        });
        DB::table('pedidos')->insert([['id' => 1, 'estado' => 'PENDIENTE'], ['id' => 2, 'estado' => 'PENDIENTE']]);
        DB::table('pedido_details')->insert([
            ['id' => 1, 'pedido_id' => 1, 'product_id' => 10, 'cantidad' => 10],
            ['id' => 2, 'pedido_id' => 2, 'product_id' => 20, 'cantidad' => 3],
        ]);
        $this->actingAs((new User())->forceFill(['id' => 1]), 'sanctum');
    }

    private function payload(int $pedidoId, int $detalleId, int $cantidad): array
    {
        return ['pedido_id' => $pedidoId, 'accion' => 'APROBADO', 'modificaciones' => [[
            'pedido_detail_id' => $detalleId, 'cantidad_aprobada' => $cantidad, 'accion' => 'COMPRAR',
        ]]];
    }

    public function test_foreign_details_are_rejected_before_any_changes_are_saved(): void
    {
        $this->postJson('/api/pedidos/accion', $this->payload(2, 1, 99))
            ->assertUnprocessable()->assertJsonValidationErrors('modificaciones.0.pedido_detail_id');
        $this->assertDatabaseHas('pedidos', ['id' => 2, 'estado' => 'PENDIENTE']);
        $this->assertDatabaseHas('pedido_details', ['id' => 1, 'cantidad' => 10]);
        $this->assertDatabaseCount('pedido_modificaciones', 0);
    }

    public function test_sequential_approvals_change_only_their_own_details(): void
    {
        $this->postJson('/api/pedidos/accion', $this->payload(1, 1, 7))->assertOk();
        $this->postJson('/api/pedidos/accion', $this->payload(2, 2, 2))->assertOk();
        $this->assertDatabaseHas('pedido_details', ['id' => 1, 'pedido_id' => 1, 'cantidad' => 7]);
        $this->assertDatabaseHas('pedido_details', ['id' => 2, 'pedido_id' => 2, 'cantidad' => 2]);
        $this->assertDatabaseCount('pedido_modificaciones', 2);
        $this->assertDatabaseCount('pedido_modificacion_detalles', 2);
    }

    public function test_zero_quantity_is_preserved_and_negative_quantity_is_rejected(): void
    {
        $this->postJson('/api/pedidos/accion', $this->payload(1, 1, 0))->assertOk();
        $this->assertDatabaseHas('pedido_details', ['id' => 1, 'cantidad' => 0]);
        $this->postJson('/api/pedidos/accion', $this->payload(2, 2, -1))
            ->assertUnprocessable()->assertJsonValidationErrors('modificaciones.0.cantidad_aprobada');
        $this->assertDatabaseHas('pedidos', ['id' => 2, 'estado' => 'PENDIENTE']);
    }
}
