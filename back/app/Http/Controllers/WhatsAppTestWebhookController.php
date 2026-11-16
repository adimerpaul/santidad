<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use JsonException;

class WhatsAppTestWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $expected = (string) config('services.whatsapp_webhook.verify_token');
        abort_if($expected === '', 503);

        // PHP convierte los puntos de hub.mode, hub.verify_token y hub.challenge en guiones bajos.
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');
        abort_unless(
            $request->query('hub_mode') === 'subscribe' &&
            is_string($token) && hash_equals($expected, $token),
            403
        );
        abort_unless(is_string($challenge) && $challenge !== '', 400);

        return response($challenge, 200, ['Content-Type' => 'text/plain', 'Cache-Control' => 'no-store']);
    }

    public function receive(Request $request): Response
    {
        $secret = (string) config('services.whatsapp_webhook.app_secret');
        $phoneId = (string) config('services.whatsapp_webhook.test_phone_number_id');
        abort_if($secret === '' || $phoneId === '', 503);

        $body = $request->getContent();
        $signature = 'sha256=' . hash_hmac('sha256', $body, $secret);
        abort_unless(hash_equals($signature, (string) $request->header('X-Hub-Signature-256')), 403);

        try {
            $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            abort(400);
        }

        abort_unless(
            ($payload['object'] ?? null) === 'whatsapp_business_account' &&
            is_array($payload['entry'] ?? null),
            400
        );

        $events = $this->testEvents($payload['entry'], $phoneId);
        if ($events !== []) {
            foreach (array_chunk($events, 100) as $batch) {
                DB::table('whatsapp_test_events')->upsert($batch, ['event_key'], ['event_key']);
            }
        }

        return response('EVENT_RECEIVED', 200, ['Content-Type' => 'text/plain']);
    }

    private function testEvents(array $entries, string $phoneId): array
    {
        $events = [];
        $receivedAt = now();
        foreach ($entries as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                if (($change['field'] ?? null) !== 'messages' ||
                    ($value['metadata']['phone_number_id'] ?? null) !== $phoneId) {
                    continue;
                }

                foreach ((array) ($value['messages'] ?? []) as $message) {
                    $this->recordEvent($events, $phoneId, 'message', $message['id'] ?? null, $receivedAt);
                }
                foreach ((array) ($value['statuses'] ?? []) as $status) {
                    $state = $status['status'] ?? null;
                    if (is_string($state) && preg_match('/\A[a-z_]{1,32}\z/', $state)) {
                        $this->recordEvent($events, $phoneId, 'status:' . $state, $status['id'] ?? null, $receivedAt);
                    }
                }
            }
        }
        return $events;
    }

    private function recordEvent(array &$events, string $phoneId, string $kind, mixed $id, \DateTimeInterface $receivedAt): void
    {
        if (!is_string($id) || $id === '') {
            return;
        }

        $key = hash('sha256', $phoneId . ':' . $kind . ':' . $id);
        $events[$key] = [
            'event_key' => $key,
            'phone_number_id' => $phoneId,
            'kind' => $kind,
            'received_at' => $receivedAt,
        ];
    }
}
