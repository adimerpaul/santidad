<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportCaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        config(['services.support_realtime' => [], 'services.whatsapp_webhook.graph_version' => 'v25.0']);
        DB::purge('sqlite');

        Schema::create('agencias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('status');
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agencia_id')->nullable();
        });
        foreach ([
            '2025_10_18_152547_create_orders_table.php',
            '2025_10_18_152820_create_order_items_table.php',
            '2026_11_15_000001_create_support_cases.php',
            '2026_11_15_000002_create_whatsapp_test_events_table.php',
            '2026_11_16_000001_add_whatsapp_thread_key_to_support_cases.php',
            '2026_11_16_000002_add_whatsapp_replies_to_support.php',
        ] as $file) {
            (require database_path('migrations/' . $file))->up();
        }
        DB::table('agencias')->insert([
            ['id' => 1, 'nombre' => 'Central', 'status' => 'ACTIVO'],
            ['id' => 2, 'nombre' => 'Norte', 'status' => 'ACTIVO'],
        ]);
        DB::table('users')->insert([
            ['id' => 1, 'agencia_id' => 1],
            ['id' => 2, 'agencia_id' => 1],
            ['id' => 3, 'agencia_id' => 2],
            ['id' => 4, 'agencia_id' => null],
        ]);
    }

    public function test_same_client_orders_are_isolated_and_notes_and_modes_persist(): void
    {
        $web = $this->order('WEB-1', 'web', 1);
        $app = $this->order('APP-2', 'app', 2);
        $this->authenticate(2);
        $this->postJson('/api/support/cases', ['order_number' => 'APP-2'])->assertNotFound();
        $first = $this->postJson('/api/support/cases', ['order_number' => 'WEB-1', 'agencia_id' => 2])
            ->assertOk()->assertJsonPath('branch_id', 1)->assertJsonPath('source', 'web')->json('id');
        $this->postJson('/api/support/cases', ['order_number' => 'WEB-1'])->assertJsonPath('id', $first);
        $this->postJson("/api/support/cases/$first/messages", ['author' => 'note', 'text' => 'Nota Central'])->assertCreated();
        $this->patchJson("/api/support/cases/$first", ['action' => 'bot'])->assertOk()->assertJsonPath('mode', 'bot');
        $this->postJson("/api/support/cases/$first/messages", ['author' => 'agent', 'text' => 'Respuesta'])
            ->assertUnprocessable();
        $this->getJson("/api/support/cases/$first")->assertJsonPath('mode', 'bot')->assertJsonPath('messages.0.text', 'Nota Central');
        $this->patchJson("/api/support/cases/$first", ['action' => 'manual'])->assertOk();
        $this->postJson("/api/support/cases/$first/messages", ['author' => 'agent', 'text' => 'Respuesta guardada'])->assertCreated();
        $this->patchJson("/api/support/cases/$first", ['action' => 'resolve'])->assertJsonPath('status', 'resolved');
        $this->postJson("/api/support/cases/$first/messages", ['author' => 'note', 'text' => 'Otra nota'])->assertUnprocessable();
        $this->patchJson("/api/support/cases/$first", ['action' => 'reopen'])->assertJsonPath('mode', 'manual');
        $this->patchJson("/api/support/cases/$first/branch", ['agencia_id' => 2])->assertForbidden();
        $this->putJson('/api/user/2', ['agencia_id' => 2])->assertForbidden();
        $this->putJson('/api/updatePassword/1', ['password' => 'example-password'])->assertForbidden();

        $this->authenticate(3);
        $second = $this->postJson('/api/support/cases', ['order_number' => 'APP-2'])
            ->assertOk()->assertJsonPath('branch_id', 2)->assertJsonPath('source', 'app')->json('id');
        $this->getJson('/api/support/cases?search=Cliente')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $second);
        $this->getJson("/api/support/cases/$first")->assertNotFound();
        $this->patchJson("/api/support/cases/$first", ['action' => 'manual'])->assertNotFound();
        $this->postJson("/api/support/cases/$first/messages", ['author' => 'note', 'text' => 'Otra sucursal'])->assertNotFound();
        $this->assertSame('pending', $web->fresh()->status);
        $this->assertSame(2, $app->fresh()->meta['sucursal_id']);
    }

    public function test_unassigned_cases_are_admin_only_until_assignment_and_can_be_transferred(): void
    {
        $this->order('DELIVERY-1', 'web', null);
        $this->getJson('/api/support/cases')->assertUnauthorized();
        $this->authenticate(2);
        $this->postJson('/api/support/cases', ['order_number' => 'DELIVERY-1'])->assertNotFound();
        $this->postJson('/api/support/cases', ['customer_name' => 'Consulta'])->assertForbidden();

        $this->authenticate(1);
        $this->getJson('/api/support/branches')->assertJsonCount(2);
        $this->postJson('/api/support/cases', ['order_number' => 'DELIVERY-1'])->assertJsonPath('branch_id', null);
        $id = $this->postJson('/api/support/cases', ['customer_name' => 'Consulta', 'message' => 'Hola'])
            ->assertOk()->assertJsonPath('status', 'pending')->assertJsonPath('messages.0.text', 'Hola')->json('id');
        $this->getJson('/api/support/cases?branch=0')->assertJsonCount(2, 'data');
        $this->authenticate(4);
        $this->getJson('/api/support/cases')->assertJsonCount(0, 'data');
        $this->getJson("/api/support/cases/$id")->assertNotFound();

        $this->authenticate(1);
        $this->patchJson("/api/support/cases/$id/branch", ['agencia_id' => 1])->assertJsonPath('branch_id', 1);
        $this->authenticate(2);
        $this->getJson("/api/support/cases/$id")->assertOk();
        $this->authenticate(1);
        $this->patchJson("/api/support/cases/$id/branch", ['agencia_id' => 2])->assertJsonPath('branch_id', 2);
        $this->authenticate(2);
        $this->getJson("/api/support/cases/$id")->assertNotFound();
        $this->authenticate(3);
        $this->getJson("/api/support/cases/$id")->assertJsonPath('messages.0.text', 'Hola');
    }

    public function test_whatsapp_text_appears_in_one_case_and_respects_branch_visibility(): void
    {
        config(['services.support_realtime' => ['url' => 'http://socket.test', 'secret' => 'support-test-secret']]);
        Http::fake(['http://socket.test/support/notify' => Http::response(null, 503)]);
        $this->postWhatsAppText('wamid.first', 'Hola, esta es una prueba')->assertOk();
        $this->postWhatsAppText('wamid.first', 'Hola, esta es una prueba')->assertOk();
        $this->postWhatsAppText('wamid.other-number', 'Otro canal', '987654321')->assertOk();
        $this->assertDatabaseCount('support_cases', 1);
        $this->assertDatabaseCount('support_messages', 1);
        $this->assertDatabaseCount('whatsapp_test_events', 1);
        $this->getJson('/api/support/realtime-ticket')->assertUnauthorized();

        $this->authenticate(1);
        $ticket = $this->getJson('/api/support/realtime-ticket')->assertOk()->json('ticket');
        [$body, $signature] = explode('.', $ticket);
        $this->assertSame('admins', json_decode(base64_decode(strtr($body, '-_', '+/')), true)['room']);
        $this->assertSame(hash_hmac('sha256', 'support-ticket:' . $body, 'support-test-secret'), $signature);
        $id = $this->getJson('/api/support/cases?branch=0')->assertOk()
            ->assertJsonPath('data.0.source', 'whatsapp')
            ->assertJsonPath('data.0.name', 'Cliente de prueba')
            ->assertJsonPath('data.0.status', 'pending')
            ->assertJsonPath('data.0.preview.text', 'Hola, esta es una prueba')->json('data.0.id');
        $this->getJson("/api/support/cases/$id")->assertJsonPath('messages.0.author', 'customer');

        $this->authenticate(3);
        $this->getJson("/api/support/cases/$id")->assertNotFound();
        $this->authenticate(1);
        $this->patchJson("/api/support/cases/$id/branch", ['agencia_id' => 2])->assertOk();
        $this->patchJson("/api/support/cases/$id", ['action' => 'resolve'])->assertOk();

        $this->postWhatsAppText('wamid.first', 'Hola, esta es una prueba')->assertOk();
        $this->getJson("/api/support/cases/$id")->assertJsonPath('status', 'resolved');
        $this->postWhatsAppText('wamid.second', 'Tengo otra consulta 📦')->assertOk();
        $this->assertDatabaseCount('support_cases', 1);

        $this->authenticate(2);
        $this->getJson("/api/support/cases/$id")->assertNotFound();
        $this->authenticate(3);
        $ticket = $this->getJson('/api/support/realtime-ticket')->assertOk()->json('ticket');
        $this->assertSame('branch:2', json_decode(base64_decode(strtr(explode('.', $ticket)[0], '-_', '+/')), true)['room']);
        $this->getJson("/api/support/cases/$id")->assertOk()
            ->assertJsonPath('branch_id', 2)
            ->assertJsonPath('status', 'open')
            ->assertJsonPath('mode', 'manual')
            ->assertJsonPath('preview.text', 'Tengo otra consulta 📦');
        Http::assertSentCount(2);
        foreach ([null, 2] as $branch) {
            Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer support-test-secret')
                && $request['cases'] === [['id' => $id, 'branch' => $branch]]);
        }
    }

    public function test_whatsapp_reply_uses_test_number_once_and_requires_the_assigned_branch_and_recent_text(): void
    {
        config(['services.whatsapp_webhook.access_token' => 'test-graph-token']);
        $url = 'https://graph.facebook.com/v25.0/123456789/messages';
        Http::fake([$url => Http::response(['messages' => [['id' => 'wamid.outgoing']]], 200)]);
        $this->postWhatsAppText('wamid.inbound', 'Hola')->assertOk();
        $id = DB::table('support_cases')->value('id');
        $payload = ['text' => 'Respuesta de prueba', 'request_id' => (string) Str::uuid()];
        $route = "/api/support/cases/$id/whatsapp-replies";

        $this->authenticate(1);
        $this->postJson($route, $payload)->assertUnprocessable();
        $this->patchJson("/api/support/cases/$id/branch", ['agencia_id' => 2])->assertOk();
        $this->authenticate(2);
        $this->postJson($route, $payload)->assertNotFound();
        $this->authenticate(3);
        $messageId = $this->postJson($route, $payload)->assertCreated()
            ->assertJsonPath('whatsapp_state', 'accepted')->json('id');
        $this->postJson($route, $payload)->assertOk()->assertJsonPath('id', $messageId);
        $this->postJson($route, array_replace($payload, ['text' => 'Otro texto']))->assertConflict();
        $this->assertDatabaseHas('support_messages', ['id' => $messageId, 'whatsapp_message_id' => 'wamid.outgoing']);
        $this->assertNotSame('59170000000', DB::table('support_cases')->where('id', $id)->value('whatsapp_recipient'));
        $this->getJson("/api/support/cases/$id")->assertJsonMissingPath('whatsapp_recipient');
        $this->travel(24)->hours();
        $this->postJson($route, ['text' => 'Fuera de plazo', 'request_id' => (string) Str::uuid()])->assertUnprocessable();

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === $url
            && $request->hasHeader('Authorization', 'Bearer test-graph-token')
            && $request['to'] === '59170000000'
            && $request['text']['body'] === $payload['text']);
    }

    public function test_whatsapp_keeps_rejected_and_unconfirmed_sends_without_replaying_them(): void
    {
        config(['services.whatsapp_webhook.access_token' => 'test-graph-token']);
        Http::fake(['https://graph.facebook.com/*' => Http::sequence()->push([], 400)->push([], 503)]);
        $this->postWhatsAppText('wamid.inbound', 'Hola')->assertOk();
        $id = DB::table('support_cases')->value('id');
        $this->authenticate(1);
        $this->patchJson("/api/support/cases/$id/branch", ['agencia_id' => 1])->assertOk();

        foreach (['failed', 'unknown'] as $state) {
            $payload = ['text' => 'Prueba ' . $state, 'request_id' => (string) Str::uuid()];
            $route = "/api/support/cases/$id/whatsapp-replies";
            $this->postJson($route, $payload)->assertCreated()->assertJsonPath('whatsapp_state', $state);
            $this->postJson($route, $payload)->assertOk()->assertJsonPath('whatsapp_state', $state);
        }
        Http::assertSentCount(2);
    }

    private function postWhatsAppText(string $messageId, string $text, string $phoneId = '123456789')
    {
        $secret = 'whatsapp-test-secret';
        config([
            'services.whatsapp_webhook.app_secret' => $secret,
            'services.whatsapp_webhook.test_phone_number_id' => '123456789',
        ]);
        $body = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [['changes' => [[
                'field' => 'messages',
                'value' => [
                    'metadata' => ['phone_number_id' => $phoneId],
                    'contacts' => [['wa_id' => '59170000000', 'profile' => ['name' => 'Cliente de prueba']]],
                    'messages' => [[
                        'from' => '59170000000', 'id' => $messageId, 'type' => 'text', 'text' => ['body' => $text],
                        'timestamp' => (string) now()->timestamp,
                    ]],
                ],
            ]]]],
        ], JSON_THROW_ON_ERROR);

        return $this->call('POST', '/api/webhooks/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=' . hash_hmac('sha256', $body, $secret),
        ], $body);
    }

    private function authenticate(int $id): void
    {
        Sanctum::actingAs(User::findOrFail($id));
    }

    private function order(string $number, string $source, ?int $branch): Order
    {
        return Order::create([
            'order_number' => $number,
            'customer_name' => 'Cliente común',
            'customer_phone' => '70000000',
            'source' => $source,
            'meta' => ['sucursal_id' => $branch],
        ]);
    }
}
