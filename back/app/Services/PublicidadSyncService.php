<?php

namespace App\Services;

use App\Models\Publicidad;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Manifiesto de publicidad por sucursal, cacheado para no consultar la base en
 * cada sondeo de las pantallas. Cualquier cambio de publicidad debe llamar a
 * changed(): invalida la caché de todas las sucursales y avisa por socket.
 */
class PublicidadSyncService
{
    private const GENERATION_KEY = 'publicidad-sync:generation';
    private const TTL_SECONDS = 600;

    public function ads(?int $agencia)
    {
        return Publicidad::where('active', true)->where(function ($query) use ($agencia) {
            $query->whereNull('agencia_id');
            if ($agencia) {
                $query->orWhere('agencia_id', $agencia);
            }
        })->orderByDesc('id')->get();
    }

    public function mediaVersion(Publicidad $ad): string
    {
        return hash('sha256', json_encode([
            $ad->id, $ad->file_id, $ad->url, (string) $ad->updated_at,
        ]));
    }

    /** Manifiesto sin server_time_ms, que debe calcularse en cada respuesta. */
    public function manifest(?int $agencia): array
    {
        $generation = Cache::get(self::GENERATION_KEY, 0);
        $key = 'publicidad-sync:' . $generation . ':' . ($agencia ?? 'global');

        return Cache::remember($key, self::TTL_SECONDS, function () use ($agencia) {
            $items = $this->ads($agencia)->map(function ($ad) {
                return array_merge($ad->toArray(), [
                    'media_version' => $this->mediaVersion($ad),
                    'duration_ms' => $ad->type === 'video' ? (int) $ad->duration_ms : 10000,
                ]);
            })->values();

            // A fixed epoch makes the cycle deterministic, including after server restarts.
            // Durations are canonical: every player must use the values returned here.
            return [
                'protocol' => 1,
                'agencia_id' => $agencia,
                'epoch_ms' => 0,
                'version' => hash('sha256', json_encode([$agencia, $items->map(fn ($ad) => [
                    $ad['media_version'], $ad['duration_ms'],
                ])->all()])),
                'ready' => $items->every(fn ($ad) => $ad['duration_ms'] > 0),
                'items' => $items->all(),
            ];
        });
    }

    public function changed($data = null): void
    {
        // Nueva generación: las claves anteriores quedan huérfanas y expiran por TTL
        Cache::forever(self::GENERATION_KEY, (int) Cache::get(self::GENERATION_KEY, 0) + 1);

        $url = env('SOCKET_SERVER_URL');
        if (!$url) {
            return;
        }
        try {
            Http::timeout(3)->post(rtrim($url, '/') . '/notify', [
                'event' => 'new_publicidad',
                'data'  => $data,
            ]);
        } catch (\Exception $e) {
            Log::warning('Could not notify socket server: ' . $e->getMessage());
        }
    }
}
