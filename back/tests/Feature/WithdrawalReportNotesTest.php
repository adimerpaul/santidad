<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WithdrawalReportNotesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'withdrawal_notes_test', 'database.connections.withdrawal_notes_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('withdrawal_notes_test');
        Schema::create('withdrawal_reports', function (Blueprint $t) {
            $t->id(); $t->integer('agencia_id'); $t->integer('user_id');
            $t->integer('mes'); $t->integer('anio'); $t->string('tipo'); $t->string('estado');
            $t->text('observaciones')->nullable(); $t->timestamps();
        });
        Schema::create('products', function (Blueprint $t) { $t->id(); $t->integer('cantidad'); });
        Schema::create('withdrawal_items', function (Blueprint $t) {
            $t->id(); $t->integer('withdrawal_report_id'); $t->integer('cantidad'); $t->string('descripcion');
        });
        DB::table('withdrawal_reports')->insert([
            'id' => 4, 'agencia_id' => 2, 'user_id' => 2, 'mes' => 11, 'anio' => 2026,
            'tipo' => 'CONTEO FISICO', 'estado' => 'ABIERTO', 'observaciones' => 'Control de Inventario automático',
        ]);
        DB::table('products')->insert(['id' => 1, 'cantidad' => 30]);
        DB::table('withdrawal_items')->insert(['id' => 1, 'withdrawal_report_id' => 4, 'cantidad' => -2, 'descripcion' => 'Nota del producto']);
        $this->login(2, 2);
    }

    private function login(int $id, ?int $agency): void
    {
        $this->actingAs((new User())->forceFill(['id' => $id, 'agencia_id' => $agency]), 'sanctum');
    }

    private function payload(array $extra = []): array
    {
        return array_replace([
            'observaciones' => "Conteo físico del 3 de noviembre.\nResponsables: Ana y Mario. Revisar productos faltantes.",
            'observaciones_originales' => 'Control de Inventario automático',
        ], $extra);
    }

    public function test_notes_persist_without_changing_items_stock_or_other_report_fields(): void
    {
        $payload = $this->payload(['estado' => 'REVISADO', 'agencia_id' => 9, 'user_id' => 99, 'items' => [], 'cantidad' => 500]);
        $this->putJson('/api/withdrawal-reports/4', $payload)->assertOk()
            ->assertJsonPath('id', 4)->assertJsonPath('observaciones', $payload['observaciones']);
        $this->assertSame($payload['observaciones'], DB::table('withdrawal_reports')->where('id', 4)->value('observaciones'));
        $this->assertSame('ABIERTO', DB::table('withdrawal_reports')->where('id', 4)->value('estado'));
        $this->assertSame(2, DB::table('withdrawal_reports')->where('id', 4)->value('agencia_id'));
        $this->assertSame(2, DB::table('withdrawal_reports')->where('id', 4)->value('user_id'));
        $this->assertSame(30, DB::table('products')->where('id', 1)->value('cantidad'));
        $this->assertSame(-2, DB::table('withdrawal_items')->where('id', 1)->value('cantidad'));
        $this->assertSame('Nota del producto', DB::table('withdrawal_items')->where('id', 1)->value('descripcion'));
    }

    public function test_foreign_branch_and_unassigned_accounts_cannot_edit_notes(): void
    {
        $this->login(3, 3);
        $this->putJson('/api/withdrawal-reports/4', $this->payload())->assertForbidden();
        $this->login(3, null);
        $this->putJson('/api/withdrawal-reports/4', $this->payload())->assertForbidden();
        $this->assertSame('Control de Inventario automático', DB::table('withdrawal_reports')->value('observaciones'));
    }

    public function test_pending_notes_are_editable_only_by_admin_and_reviewed_notes_are_read_only(): void
    {
        DB::table('withdrawal_reports')->update(['estado' => 'PENDIENTE']);
        $this->putJson('/api/withdrawal-reports/4', $this->payload())->assertUnprocessable();
        $this->login(1, 1);
        $this->putJson('/api/withdrawal-reports/4', $this->payload())->assertOk();
        DB::table('withdrawal_reports')->update(['estado' => 'REVISADO']);
        $this->putJson('/api/withdrawal-reports/4', $this->payload())->assertUnprocessable();
        $this->login(2, 2);
        $this->putJson('/api/withdrawal-reports/4', $this->payload())->assertUnprocessable();
    }

    public function test_observed_reports_allow_the_branch_to_correct_general_notes(): void
    {
        DB::table('withdrawal_reports')->update(['estado' => 'OBSERVADO']);
        $this->putJson('/api/withdrawal-reports/4', $this->payload())->assertOk();
        $this->assertSame('OBSERVADO', DB::table('withdrawal_reports')->value('estado'));
    }

    public function test_stale_edit_is_rejected_without_overwriting_the_newer_notes(): void
    {
        $this->putJson('/api/withdrawal-reports/4', $this->payload(['observaciones' => 'Primera edición']))->assertOk();
        // Retrying a successful save with a lost response is harmless.
        $this->putJson('/api/withdrawal-reports/4', $this->payload(['observaciones' => 'Primera edición']))->assertOk();
        $this->putJson('/api/withdrawal-reports/4', $this->payload(['observaciones' => 'Texto viejo']))->assertConflict()
            ->assertJsonPath('observaciones', 'Primera edición');
        $this->assertSame('Primera edición', DB::table('withdrawal_reports')->value('observaciones'));
    }

    public function test_validation_and_explicit_clearing(): void
    {
        $this->putJson('/api/withdrawal-reports/4', ['observaciones' => 'Sin original'])->assertUnprocessable();
        $this->putJson('/api/withdrawal-reports/4', $this->payload(['observaciones' => str_repeat('a', 5001)]))->assertUnprocessable();
        $this->putJson('/api/withdrawal-reports/4', $this->payload(['observaciones' => null]))->assertOk()->assertJsonPath('observaciones', null);
        $this->putJson('/api/withdrawal-reports/4', $this->payload(['observaciones_originales' => '', 'observaciones' => 'Nuevo conteo']))->assertOk();
        $this->putJson('/api/withdrawal-reports/9999', $this->payload())->assertNotFound();
    }

    public function test_retired_products_listing_is_forbidden_for_branch_accounts_including_central(): void
    {
        $this->getJson('/api/withdrawal-reports/all-items')->assertForbidden();
        $this->login(3, 1);
        $this->getJson('/api/withdrawal-reports/all-items?rowsPerPage=0')->assertForbidden();
    }
}
