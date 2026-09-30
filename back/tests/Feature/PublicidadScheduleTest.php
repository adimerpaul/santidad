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
    public function test_unknown_duration_is_measured_once_without_changing_file_version(): void
    {
        $video = $this->ad(1, 'video');
        $service = app(PublicidadSyncService::class);
        $state = $service->snapshot(1);
        $this->assertFalse($state['ready']);
        $payload = ['agencia_id' => 1, 'items' => [['id' => $video->id, 'media_version' => $service->mediaVersion($video), 'duration_ms' => 30000]]];
        $this->postJson('/api/publicidad-sync/durations', $payload)->assertOk();
        $ready = $service->snapshot(1);
        $this->assertTrue($ready['ready']);
        $this->assertSame(30000, $ready['items'][0]['duration_ms']);
        $payload['items'][0]['duration_ms'] = 1000;
        $this->postJson('/api/publicidad-sync/durations', $payload)->assertOk();
        $this->assertSame($ready, $service->snapshot(1));
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
    public function test_toggle_rolls_back_without_notifying_when_schedule_cannot_be_saved(): void
    {
        $ad = $this->ad(null);
        Schema::drop('publicidad_schedules');
        $request = \Illuminate\Http\Request::create('/toggle', 'POST', ['active' => 0]);
        try {
            app(\App\Http\Controllers\PublicidadController::class)->toggleActive($request, $ad->id);
            $this->fail('Expected missing schedule table to reject the operation');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertTrue((bool) $ad->fresh()->active);
            Http::assertNothingSent();
        }
    }
    public function test_setting_active_is_idempotent_and_commits_before_socket_delivery(): void
    {
        $ad = $this->ad(null);
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(function () {
            $this->assertSame(0, DB::transactionLevel());
            return Http::response([], 503);
        });
        $controller = app(\App\Http\Controllers\PublicidadController::class);
        $request = \Illuminate\Http\Request::create('/toggle', 'POST', ['active' => 0]);
        $controller->toggleActive($request, $ad->id);
        $revision = DB::table('publicidad_schedules')->where('agencia_id', 1)->value('revision');
        $controller->toggleActive($request, $ad->id);
        $this->assertFalse((bool) $ad->fresh()->active);
        $this->assertSame($revision, DB::table('publicidad_schedules')->where('agencia_id', 1)->value('revision'));
        $this->assertSame(2, DB::table('publicidad_schedules')->where('pending', true)->count());
        $controller->toggleActive(\Illuminate\Http\Request::create('/toggle', 'POST'), $ad->id);
        $this->assertTrue((bool) $ad->fresh()->active);
    }

}
