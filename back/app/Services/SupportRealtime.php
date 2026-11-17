<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SupportRealtime
{
    public function ticket(User $user): ?string
    {
        $secret = (string) config('services.support_realtime.secret');
        $room = (int) $user->id === 1 ? 'admins' : 'branch:' . (int) $user->agencia_id;
        if ($secret === '' || $room === 'branch:0') {
            return null;
        }

        $body = rtrim(strtr(base64_encode(json_encode([
            'room' => $room,
            'exp' => now()->timestamp + 120,
        ], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');

        return $body . '.' . hash_hmac('sha256', 'support-ticket:' . $body, $secret);
    }

    public function notify(array $cases): void
    {
        $secret = (string) config('services.support_realtime.secret');
        $url = rtrim((string) config('services.support_realtime.url'), '/');
        if ($cases === [] || $secret === '' || $url === '') {
            return;
        }

        try {
            Http::withToken($secret)->connectTimeout(0.4)->timeout(0.8)->post($url . '/support/notify', [
                'cases' => array_values($cases),
            ]);
        } catch (ConnectionException) {
            // La bandeja se sincroniza al reconectar y conserva la actualización manual.
        }
    }
}
