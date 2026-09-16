<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicidadSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sync_test', 'database.connections.sync_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('sync_test');
        Schema::create('publicidads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_id');
            $table->string('url');
            $table->string('type');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('agencia_id')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();
        });
        foreach ([[1, null, 'image'], [2, 1, 'video'], [3, 2, 'image'], [4, 1, 'image']] as [$id, $branch, $type]) {
            DB::table('publicidads')->insert([
                'id' => $id, 'agencia_id' => $branch, 'type' => $type,
                'name' => 'Ad '.$id, 'file_id' => 'publicidad/'.$id.'.mp4',
                'url' => 'https://example.test/'.$id, 'active' => $id !== 4,
                'created_at' => '2026-09-09 00:00:00', 'updated_at' => '2026-09-09 00:00:00',
            ]);
        }
    }

    public function test_branch_filter_order_no_cache_and_legacy_contract(): void
    {
        $result = $this->getJson('/api/publicidad-sync?agencia_id=1')->assertOk();
        $this->assertSame([2, 1], array_column($result->json('items'), 'id'));
        $this->assertFalse($result->json('ready'));
        $this->assertSame(10000, $result->json('items.1.duration_ms'));
        $this->assertStringContainsString('no-store', $result->headers->get('Cache-Control'));
        $this->assertLessThan(2000, abs(microtime(true) * 1000 - $result->json('server_time_ms')));
        $this->assertSame([3, 1], array_column($this->getJson('/api/publicidad-sync?agencia_id=2')->json('items'), 'id'));
        $this->assertSame([1], array_column($this->getJson('/api/publicidad-sync')->json('items'), 'id'));
        $legacy = $this->getJson('/api/publicidad-actual?agencia_id=1')->assertOk()->json();
        $this->assertTrue(array_is_list($legacy));
        $this->assertSame([2, 1], array_column($legacy, 'id'));
    }

    public function test_measurement_is_canonical_and_does_not_change_media_version(): void
    {
        $before = $this->getJson('/api/publicidad-sync?agencia_id=1')->json();
        $report = ['agencia_id' => 1, 'items' => [[
            'id' => 2, 'media_version' => $before['items'][0]['media_version'], 'duration_ms' => 23000,
        ]]];
        $this->postJson('/api/publicidad-sync/durations', $report)->assertOk();
        $report['items'][0]['duration_ms'] = 24000;
        $this->postJson('/api/publicidad-sync/durations', $report)->assertOk();
        $after = $this->getJson('/api/publicidad-sync?agencia_id=1')->json();
        $this->assertTrue($after['ready']);
        $this->assertSame(23000, $after['items'][0]['duration_ms']);
        $this->assertSame($before['items'][0]['media_version'], $after['items'][0]['media_version']);
        $this->assertNotSame($before['version'], $after['version']);
        $this->assertSame($after['version'], $this->getJson('/api/publicidad-sync?agencia_id=1')->json('version'));
    }

    public function test_stale_and_other_branch_measurements_are_ignored(): void
    {
        $ad = $this->getJson('/api/publicidad-sync?agencia_id=1')->json('items.0');
        $payload = ['agencia_id' => 2, 'items' => [['id' => 2, 'media_version' => $ad['media_version'], 'duration_ms' => 20000]]];
        $this->postJson('/api/publicidad-sync/durations', $payload)->assertOk();
        $payload['agencia_id'] = 1;
        $payload['items'][0]['media_version'] = str_repeat('0', 64);
        $this->postJson('/api/publicidad-sync/durations', $payload)->assertOk();
        $this->assertNull(DB::table('publicidads')->where('id', 2)->value('duration_ms'));
        $payload['items'][0]['duration_ms'] = -1;
        $this->postJson('/api/publicidad-sync/durations', $payload)->assertUnprocessable();
    }
}
