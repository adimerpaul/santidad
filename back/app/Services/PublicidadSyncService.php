<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Avisos de publicidad a las pantallas a través del servidor de sockets.
 * Las pantallas no consultan la API periódicamente: la playlist les llega en
 * el evento `publicidad_play` que se envía desde el panel.
 */
class PublicidadSyncService
{
    public function changed($data = null): void
    {
        $this->notify('new_publicidad', $data);
    }

    /** Emite un evento a todas las pantallas a través del servidor de sockets. */
    public function notify(string $event, $data = null): bool
    {
        $url = env('SOCKET_SERVER_URL');
        if (!$url) {
            return false;
        }
        try {
            return Http::timeout(3)->post(rtrim($url, '/') . '/notify', [
                'event' => $event,
                'data'  => $data,
            ])->successful();
        } catch (\Exception $e) {
            Log::warning('Could not notify socket server: ' . $e->getMessage());
            return false;
        }
    }
}
