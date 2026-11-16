<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WhatsAppTestWebhookTest extends TestCase
{
    private const URI = '/api/webhooks/whatsapp';
    private const SECRET = 'test-app-secret';
    private const PHONE_ID = '123456789';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'services.whatsapp_webhook' => [
                'verify_token' => 'test-verify-token',
                'app_secret' => self::SECRET,
                'test_phone_number_id' => self::PHONE_ID,
            ],
        ]);
        DB::purge('sqlite');
        (require database_path('migrations/2026_11_15_000002_create_whatsapp_test_events_table.php'))->up();
    }

    public function test_verifies_meta_challenge_and_rejects_wrong_token(): void
    {
        $this->get(self::URI . '?hub.mode=subscribe&hub.verify_token=test-verify-token&hub.challenge=001234')
            ->assertOk()->assertContent('001234')->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->get(self::URI . '?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=123')->assertForbidden();
        $this->get(self::URI . '?hub.mode=subscribe&hub.verify_token=test-verify-token')->assertBadRequest();
    }

    public function test_records_signed_events_for_test_number_once_even_when_rebatched(): void
    {
        $event = $this->change(self::PHONE_ID);
        $foreign = $this->change('999999999');
        $this->postSigned($this->body([$foreign, $event]))->assertOk()->assertContent('EVENT_RECEIVED');
        $this->postSigned($this->body([$event]))->assertOk();
        $this->postSigned($this->body([$foreign]))->assertOk();

        $records = DB::table('whatsapp_test_events')->orderBy('kind')->get();
        $this->assertSame(['message', 'status:delivered', 'status:read'], $records->pluck('kind')->all());
        $this->assertSame([self::PHONE_ID], $records->pluck('phone_number_id')->unique()->values()->all());
        $this->assertStringNotContainsString('Hola privado', $records->toJson());
        $this->assertStringNotContainsString('wamid.test-1', $records->toJson());
    }

    public function test_rejects_unsigned_tampered_and_malformed_requests(): void
    {
        $body = $this->body([$this->change(self::PHONE_ID)]);
        $this->call('POST', self::URI, [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)
            ->assertForbidden();
        $this->postSigned(str_replace('Hola privado', 'Cambiado', $body), $body)->assertForbidden();
        $this->postSigned('{broken')->assertBadRequest();
        $this->assertDatabaseCount('whatsapp_test_events', 0);
    }

    public function test_refuses_requests_until_credentials_are_set(): void
    {
        config([
            'services.whatsapp_webhook.verify_token' => null,
            'services.whatsapp_webhook.app_secret' => null,
        ]);
        $this->get(self::URI . '?hub.mode=subscribe&hub.verify_token=test-verify-token&hub.challenge=123')
            ->assertServiceUnavailable();
        $this->postSigned($this->body([$this->change(self::PHONE_ID)]))->assertServiceUnavailable();
        $this->assertDatabaseCount('whatsapp_test_events', 0);
    }

    private function postSigned(string $body, ?string $bodyToSign = null)
    {
        return $this->call('POST', self::URI, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=' . hash_hmac('sha256', $bodyToSign ?? $body, self::SECRET),
        ], $body);
    }

    private function body(array $changes): string
    {
        return json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [['id' => 'waba1', 'changes' => $changes]],
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }

    private function change(string $phoneId): array
    {
        return [
            'field' => 'messages',
            'value' => [
                'metadata' => ['phone_number_id' => $phoneId],
                'messages' => [['id' => 'wamid.test-1', 'type' => 'text', 'text' => ['body' => 'Hola privado']]],
                'statuses' => [
                    ['id' => 'wamid.test-2', 'status' => 'delivered'],
                    ['id' => 'wamid.test-2', 'status' => 'read'],
                ],
            ],
        ];
    }
}
