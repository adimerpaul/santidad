<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashClosureReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Dedicated in-memory connection: never migrate or write the local pharmacy database.
        config(['database.default' => 'closure_report_test', 'database.connections.closure_report_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('closure_report_test');
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('agencias', function (Blueprint $t) { $t->id(); $t->string('nombre'); });
        Schema::create('cash_closures', function (Blueprint $t) {
            $t->id(); $t->integer('agencia_id'); $t->integer('user_id'); $t->integer('closed_by_user_id')->nullable();
            $t->dateTime('fecha_apertura'); $t->dateTime('fecha_cierre')->nullable(); $t->string('estado');
            foreach (['monto_fisico', 'monto_sistema_efectivo', 'monto_sistema_digital', 'monto_sistema_total', 'diferencia'] as $field) {
                $t->double($field)->nullable();
            }
            $t->text('observaciones')->nullable(); $t->text('observaciones_apertura')->nullable();
        });
        DB::table('users')->insert([['id' => 1, 'name' => 'Administrador'], ['id' => 2, 'name' => 'Ana'], ['id' => 3, 'name' => 'Luis']]);
        DB::table('agencias')->insert([['id' => 1, 'nombre' => 'Matriz'], ['id' => 2, 'nombre' => 'Sucursal']]);
        $this->login(1, 1);
    }

    private function login(int $id, ?int $agency): void
    {
        $this->actingAs((new User())->forceFill(['id' => $id, 'agencia_id' => $agency]), 'sanctum');
    }

    private function closure(array $overrides = []): int
    {
        return DB::table('cash_closures')->insertGetId(array_replace([
            'agencia_id' => 1, 'user_id' => 2, 'closed_by_user_id' => 1,
            'fecha_apertura' => '2026-11-01 08:00:00', 'fecha_cierre' => '2026-11-01 20:00:00',
            'estado' => 'CERRADO', 'monto_fisico' => 100, 'monto_sistema_efectivo' => 100,
            'monto_sistema_digital' => 50, 'monto_sistema_total' => 150, 'diferencia' => 0,
        ], $overrides));
    }

    public function test_thresholds_are_strict_and_pending_and_open_shifts_are_not_anomalies(): void
    {
        foreach ([-20, -20.01, 150, 150.01, -0.01, 0] as $difference) $this->closure(['diferencia' => $difference]);
        $this->closure(['estado' => 'PENDIENTE', 'diferencia' => -500]);
        $this->closure(['estado' => 'ABIERTO', 'fecha_cierre' => null, 'diferencia' => 500]);
        $this->getJson('/api/cash-closures/report')->assertOk()
            ->assertJsonPath('resumen.turnos', 8)->assertJsonPath('resumen.cerrados', 6)
            ->assertJsonPath('resumen.faltantes', 1)->assertJsonPath('resumen.sobrantes', 1)
            ->assertJsonPath('resumen.anomalias', 2)->assertJsonPath('resumen.monto_faltante', 20.01)
            ->assertJsonPath('resumen.monto_sobrante', 150.01)->assertJsonPath('resumen.pendientes', 1);
        $this->getJson('/api/cash-closures/report?vista=anomalias')->assertOk()->assertJsonPath('cierres.total', 2);
        $this->getJson('/api/cash-closures/report?vista=faltantes')->assertOk()->assertJsonPath('cierres.total', 1);
        $this->getJson('/api/cash-closures/report?vista=sobrantes')->assertOk()->assertJsonPath('cierres.total', 1);
        $this->getJson('/api/cash-closures/report?vista=pendientes')->assertOk()->assertJsonPath('cierres.total', 1);
    }

    public function test_summary_covers_more_than_200_records_and_is_independent_of_page_and_view(): void
    {
        for ($i = 0; $i < 205; $i++) $this->closure(['diferencia' => $i < 40 ? -25 : 0]);
        $this->getJson('/api/cash-closures/report?per_page=15&page=2&vista=anomalias')->assertOk()
            ->assertJsonPath('resumen.turnos', 205)->assertJsonPath('resumen.anomalias', 40)
            ->assertJsonPath('resumen.monto_faltante', 1000)->assertJsonPath('cierres.total', 40)
            ->assertJsonPath('cierres.current_page', 2)->assertJsonCount(15, 'cierres.data');
    }

    public function test_filters_use_shift_owner_not_closer_and_dates_include_entire_last_day(): void
    {
        $id = $this->closure(['fecha_cierre' => '2026-11-02 23:59:59', 'diferencia' => -50]);
        $this->closure(['user_id' => 3]);
        $this->closure(['agencia_id' => 2, 'diferencia' => -100]);
        $this->closure(['fecha_cierre' => '2026-11-03 00:00:00']);
        $this->getJson('/api/cash-closures/report?agencia_id=1&user_id=2&desde=2026-11-02&hasta=2026-11-02')->assertOk()
            ->assertJsonPath('resumen.turnos', 1)->assertJsonPath('cierres.data.0.id', $id)
            ->assertJsonPath('cierres.data.0.user.name', 'Ana')->assertJsonPath('cierres.data.0.closed_by_user.name', 'Administrador');
        $this->getJson('/api/cash-closures/report?user_id=1')->assertOk()->assertJsonPath('cierres.total', 0);
        $this->getJson('/api/cash-closures/report?hasta=2026-11-01')->assertOk()->assertJsonPath('cierres.total', 2);
    }

    public function test_non_admin_is_scoped_even_with_forged_branch_and_cannot_infer_differences(): void
    {
        $this->closure(['diferencia' => -500]);
        $this->closure(['agencia_id' => 2, 'user_id' => 3, 'diferencia' => 700]);
        $this->login(2, 1);
        $this->getJson('/api/cash-closures/report?agencia_id=2')->assertOk()->assertJsonPath('cierres.total', 1)
            ->assertJsonPath('cierres.data.0.agencia_id', 1)->assertJsonMissingPath('cierres.data.0.diferencia')
            ->assertJsonMissingPath('cierres.data.0.monto_sistema_total')->assertJsonMissingPath('cierres.data.0.monto_sistema_efectivo')
            ->assertJsonMissingPath('cierres.data.0.monto_sistema_digital')->assertJsonMissingPath('resumen.anomalias')
            ->assertJsonMissingPath('resumen.monto_faltante');
        $this->getJson('/api/cash-closures/report?vista=anomalias')->assertForbidden();
        $this->getJson('/api/cash-closures/report?agencia_id=2&user_id=3')->assertOk()->assertJsonPath('cierres.total', 0);
        $this->getJson('/api/cash-closures/report-options')->assertOk()->assertJsonCount(1, 'agencias')
            ->assertJsonCount(1, 'cuentas')->assertJsonPath('cuentas.0.id', 2)->assertJsonPath('cuentas.0.agencia_ids.0', 1);
        $this->login(2, null);
        $this->getJson('/api/cash-closures/report')->assertOk()->assertJsonPath('cierres.total', 0);
    }

    public function test_options_include_historical_branches_of_accounts_and_only_public_name_fields(): void
    {
        $this->closure();
        $this->closure(['agencia_id' => 2]);
        $this->getJson('/api/cash-closures/report-options')->assertOk()->assertJsonCount(2, 'agencias')
            ->assertJsonCount(1, 'cuentas')->assertJsonPath('cuentas.0.id', 2)->assertJsonCount(2, 'cuentas.0.agencia_ids')
            ->assertJsonMissingPath('cuentas.0.email');
    }

    public function test_open_shift_uses_opening_date_and_invalid_filters_are_rejected(): void
    {
        $this->closure(['estado' => 'ABIERTO', 'fecha_apertura' => '2026-11-03 08:00:00', 'fecha_cierre' => null]);
        $this->getJson('/api/cash-closures/report?desde=2026-11-03&hasta=2026-11-03&vista=abiertos')->assertOk()->assertJsonPath('cierres.total', 1);
        foreach (['desde=2026-11-04&hasta=2026-11-03', 'vista=otra', 'per_page=10000', 'page=0', 'hasta=ayer'] as $query) {
            $this->getJson('/api/cash-closures/report?'.$query)->assertUnprocessable();
        }
    }
}
