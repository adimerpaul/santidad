<?php
namespace Tests\Feature;

use App\Models\Publicidad;
use App\Services\PublicidadSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicidadScheduleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('agencias', function (Blueprint $t) { $t->id(); $t->string('nombre'); });
        Schema::create('publicidads', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('file_id'); $t->string('type'); $t->string('url');
            $t->boolean('active')->default(true); $t->unsignedBigInteger('agencia_id')->nullable();
            $t->unsignedInteger('duration_ms')->nullable(); $t->timestamps();
        });
        (require database_path('migrations/2026_09_30_000001_create_publicidad_schedules.php'))->up();
        DB::table('agencias')->insert([['id' => 1, 'nombre' => 'A'], ['id' => 2, 'nombre' => 'B']]);
        config(['services.publicidad.socket_url' => 'http://socket.test']);
        Http::fake(['*' => Http::response(['success' => true])]);
    }
    private function ad(?int $agency, string $type = 'image'): Publicidad
    {
        return Publicidad::create(['name' => 'Test', 'file_id' => uniqid().'.mp4', 'url' => 'https://example.com/test', 'type' => $type, 'agencia_id' => $agency, 'active' => true]);
    }
    public function test_scopes_and_explicit_restart_have_persistent_independent_revisions(): void
    {
        $global = $this->ad(null); $local = $this->ad(1); $this->ad(2);
        $service = app(PublicidadSyncService::class);
        $a = $service->snapshot(1); $b = $service->snapshot(2);
        $this->assertSame([$local->id, $global->id], array_column($a['items'], 'id'));
        $this->assertTrue($a['ready']);
        $this->assertSame($a, $service->snapshot(1));
        $next = $service->publish(1, true)[0];
        $this->assertSame($a['version'], $next['version']);
        $this->assertSame($a['revision'] + 1, $next['revision']);
        $this->assertSame($b, $service->snapshot(2));
        $this->assertFalse((bool) DB::table('publicidad_schedules')->where('agencia_id', 1)->value('pending'));
    }
    public function test_players_cannot_poll_the_disabled_sync_api(): void
    {
        $this->getJson('/api/publicidad-sync?agencia_id=1')->assertNotFound();
        $this->postJson('/api/publicidad-sync/durations', [])->assertNotFound();
    }

    public function test_daily_publication_restarts_all_branches_with_the_same_epoch(): void
    {
        $this->ad(null);
        $this->artisan('publicidad:publish-pending --daily')->assertSuccessful();
        $service = app(PublicidadSyncService::class);
        $this->assertSame($service->snapshot(1)['epoch_ms'], $service->snapshot(2)['epoch_ms']);
        Http::assertSentCount(2);
        $events = app(\Illuminate\Console\Scheduling\Schedule::class)->events();
        $this->assertCount(1, $events);
        $this->assertSame('0 9 * * *', $events[0]->expression);
        $this->assertSame('America/La_Paz', $events[0]->timezone);
    }

    public function test_manual_play_publishes_once_to_the_selected_branch(): void
    {
        $this->ad(null);
        $response = app(\App\Http\Controllers\PublicidadController::class)->play(
            \Illuminate\Http\Request::create('/play', 'POST', ['agencia_id' => 1])
        );
        $this->assertSame(1, $response->getData(true)['items']);
        $this->assertFalse($response->getData(true)['pending']);
        Http::assertSentCount(1);
        $this->assertSame(1, DB::table('publicidad_schedules')->count());
    }

    public function test_failed_delivery_is_durable_and_retries_only_pending_states(): void
    {
        $this->ad(1);
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['*' => Http::response([], 503)]);
        $service = app(PublicidadSyncService::class);
        $service->publish(1);
        $this->assertTrue((bool) DB::table('publicidad_schedules')->value('pending'));
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['*' => Http::response(['success' => true])]);
        $service->retryPending();
        $this->assertFalse((bool) DB::table('publicidad_schedules')->value('pending'));
    }
    public function test_toggle_only_saves_until_manual_or_daily_publication(): void
    {
        $ad = $this->ad(null);
        $controller = app(\App\Http\Controllers\PublicidadController::class);
        $controller->toggleActive(\Illuminate\Http\Request::create('/toggle', 'POST', ['active' => 0]), $ad->id);
        $this->assertFalse((bool) $ad->fresh()->active);
        Http::assertNothingSent();
        $this->assertSame(0, DB::table('publicidad_schedules')->count());
    }
}
