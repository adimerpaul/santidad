<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class WhatsAppTestReplies
{
    public function phoneId(): string
    {
        return (string) config('services.whatsapp_webhook.test_phone_number_id');
    }

    public function isConfigured(): bool
    {
        return preg_match('/\A[0-9]{1,32}\z/', $this->phoneId())
            && preg_match('/\Av[0-9]+\.0\z/', (string) config('services.whatsapp_webhook.graph_version'))
            && trim((string) config('services.whatsapp_webhook.access_token')) !== '';
    }

    public function send(string $recipient, string $text): array
    {
        $version = config('services.whatsapp_webhook.graph_version');
        $url = 'https://graph.facebook.com/' . $version . '/' . $this->phoneId() . '/messages';

        try {
            $response = Http::withToken(config('services.whatsapp_webhook.access_token'))
                ->withoutRedirecting()->connectTimeout(3)->timeout(8)->post($url, [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $recipient,
                    'type' => 'text',
                    'text' => ['body' => $text],
                ]);
        } catch (ConnectionException) {
            return ['unknown', null, null];
        }

        $id = $response->json('messages.0.id');
        if ($response->successful() && is_string($id) && $id !== '') {
            return ['accepted', $id, null];
        }

        $diagnostic = [
            'http_status' => $response->status(),
            'code' => filter_var($response->json('error.code'), FILTER_VALIDATE_INT) ?: null,
            'subcode' => filter_var($response->json('error.error_subcode'), FILTER_VALIDATE_INT) ?: null,
        ];

        return [$response->clientError() && $response->status() !== 408 ? 'failed' : 'unknown', null, $diagnostic];
    }
}
