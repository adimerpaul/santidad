<?php

namespace App\Http\Controllers;

use App\Models\SupportCase;
use App\Services\SupportRealtime;
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

    public function receive(Request $request, SupportRealtime $realtime): Response
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

        $realtime->notify($this->storeTestEvents($payload['entry'], $phoneId));

        return response('EVENT_RECEIVED', 200, ['Content-Type' => 'text/plain']);
    }

    private function storeTestEvents(array $entries, string $phoneId): array
    {
        $events = [];
        $cases = [];
        $receivedAt = now();
        foreach ($entries as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                if (($change['field'] ?? null) !== 'messages' ||
                    ($value['metadata']['phone_number_id'] ?? null) !== $phoneId) {
                    continue;
                }

                foreach ((array) ($value['messages'] ?? []) as $message) {
                    $event = $this->testEvent($phoneId, 'message', $message['id'] ?? null, $receivedAt);
                    if ($event === null) {
                        continue;
                    }

                    $sender = $message['from'] ?? null;
                    $text = data_get($message, 'text.body');
                    if (($message['type'] ?? null) === 'text' && is_string($sender) && trim($sender) !== ''
                        && is_string($text) && trim($text) !== '') {
                        $name = $this->customerName((array) ($value['contacts'] ?? []), $sender);
                        $case = $this->storeInboxMessage($event, $sender, $name, $text, $message['timestamp'] ?? null);
                        if ($case) {
                            $cases[$case->id] = [
                                'id' => $case->id,
                                'branch' => $case->agencia_id === null ? null : (int) $case->agencia_id,
                            ];
                        }
                    } else {
                        $events[$event['event_key']] = $event;
                    }
                }
                foreach ((array) ($value['statuses'] ?? []) as $status) {
                    $state = $status['status'] ?? null;
                    if (is_string($state) && preg_match('/\A[a-z_]{1,32}\z/', $state)) {
                        $event = $this->testEvent($phoneId, 'status:' . $state, $status['id'] ?? null, $receivedAt);
                        if ($event !== null) {
                            $events[$event['event_key']] = $event;
                        }
                    }
                }
            }
        }

        foreach (array_chunk($events, 100) as $batch) {
            DB::table('whatsapp_test_events')->upsert($batch, ['event_key'], ['event_key']);
        }

        return $cases;
    }

    private function testEvent(string $phoneId, string $kind, mixed $id, \DateTimeInterface $receivedAt): ?array
    {
        if (!is_string($id) || $id === '') {
            return null;
        }

        return [
            'event_key' => hash('sha256', $phoneId . ':' . $kind . ':' . $id),
            'phone_number_id' => $phoneId,
            'kind' => $kind,
            'received_at' => $receivedAt,
        ];
    }

    private function customerName(array $contacts, string $sender): string
    {
        foreach ($contacts as $contact) {
            $name = data_get($contact, 'profile.name');
            if (($contact['wa_id'] ?? null) === $sender && is_string($name) && trim($name) !== '') {
                return mb_substr(trim($name), 0, 160);
            }
        }

        return 'Contacto de WhatsApp';
    }

    private function storeInboxMessage(array $event, string $sender, string $name, string $text, mixed $timestamp): ?SupportCase
    {
        return DB::transaction(function () use ($event, $sender, $name, $text, $timestamp) {
            // El registro del evento y el mensaje se confirman juntos para admitir reintentos de Meta.
            if (!DB::table('whatsapp_test_events')->insertOrIgnore($event)) {
                return null;
            }

            $threadKey = hash('sha256', $event['phone_number_id'] . ':' . $sender);
            $receivedAt = $event['received_at'];
            DB::table('support_cases')->upsert([[
                'whatsapp_thread_key' => $threadKey,
                'customer_name' => $name,
                'source' => 'whatsapp',
                'created_at' => $receivedAt,
                'updated_at' => $receivedAt,
            ]], ['whatsapp_thread_key'], ['whatsapp_thread_key']);

            $case = SupportCase::where('whatsapp_thread_key', $threadKey)->lockForUpdate()->firstOrFail();
            $sentAt = filter_var($timestamp, FILTER_VALIDATE_INT);
            if (preg_match('/\A[1-9][0-9]{5,14}\z/', $sender) && $sentAt > 0
                && $sentAt <= $receivedAt->getTimestamp() + 60 && $sentAt > $case->whatsapp_last_inbound) {
                $case->whatsapp_recipient = $sender;
                $case->whatsapp_phone_id = $event['phone_number_id'];
                $case->whatsapp_last_inbound = min($sentAt, $receivedAt->getTimestamp());
            }
            if ($case->status === 'resolved') {
                $case->status = $case->agencia_id ? 'open' : 'pending';
                $case->mode = 'manual';
            }
            $case->touch();
            $case->messages()->create(['author' => 'customer', 'text' => $text]);

            return $case;
        }, 3);
    }
}
