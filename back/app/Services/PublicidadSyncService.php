<?php
namespace App\Services;

use App\Models\Agencia;
use App\Models\Publicidad;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PublicidadSyncService
{
    public function mediaVersion(Publicidad $ad): string
    {
        return $ad->media_sha256 ?: hash('sha256', $ad->id.'|'.$ad->file_id.'|'.$ad->url.'|'.$ad->created_at);
    }

    public function snapshot(int $agencia): array
    {
        $row = DB::table('publicidad_schedules')->where('agencia_id', $agencia)->first();
        return $row ? json_decode($row->payload, true) : $this->build($agencia);
    }

    private function build(int $agencia, bool $restart = false, ?int $startId = null): array
    {
        return DB::transaction(function () use ($agencia, $restart, $startId) {
            DB::table('publicidad_schedules')->insertOrIgnore([
                'agencia_id' => $agencia, 'revision' => 0, 'payload' => '{}',
                'pending' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $row = DB::table('publicidad_schedules')->where('agencia_id', $agencia)->lockForUpdate()->first();
            $previous = json_decode($row->payload, true);
            $items = Publicidad::where('active', true)
                ->where(fn ($q) => $q->whereNull('agencia_id')->orWhere('agencia_id', $agencia))
                ->orderByDesc('id')->get()->map(fn ($ad) => [
                    'id' => $ad->id, 'name' => $ad->name, 'file_id' => $ad->file_id,
                    'url' => $ad->url, 'type' => $ad->type, 'agencia_id' => $ad->agencia_id,
                    'media_version' => $this->mediaVersion($ad), 'sha256' => $ad->media_sha256,
                    'size_bytes' => $ad->size_bytes,
                    'duration_ms' => $ad->type === 'video' ? ($ad->duration_ms ? (int) $ad->duration_ms : null) : 10000,
                ])->all();
            $startId = $restart ? $startId : ($previous['start_id'] ?? null);
            if ($startId !== null) {
                $index = array_search($startId, array_column($items, 'id'));
                if ($index !== false) $items = array_merge(array_slice($items, $index), array_slice($items, 0, $index));
            }
            $version = hash('sha256', json_encode($items));
            if (!$restart && ($previous['version'] ?? null) === $version) return $previous;
            $ready = !collect($items)->contains(fn ($ad) => !$ad['duration_ms']);
            $state = [
                'protocol' => 2, 'agencia_id' => $agencia, 'revision' => (int) $row->revision + 1,
                'version' => $version, 'ready' => $ready, 'start_id' => $startId,
                'epoch_ms' => (int) round(microtime(true) * 1000) + 5000, 'items' => $items,
            ];
            DB::table('publicidad_schedules')->where('agencia_id', $agencia)->update([
                'revision' => $state['revision'], 'payload' => json_encode($state), 'pending' => true, 'updated_at' => now(),
            ]);
            return $state;
        });
    }

    public function changed($ad = null): void
    {
        $this->publish($ad?->agencia_id ? (int) $ad->agencia_id : null);
        DB::afterCommit(fn () => $this->notify('new_publicidad', $ad)); // Compatibility during a rolling upgrade.
    }

    public function publish(?int $agencia = null, bool $restart = false, ?int $startId = null): array
    {
        $states = [];
        $ids = $agencia ? [$agencia] : Agencia::pluck('id')->all();
        foreach ($ids as $id) {
            $state = $this->build((int) $id, $restart, $startId);
            $states[] = $state;
            DB::afterCommit(fn () => $this->deliver($state));
        }
        return $states;
    }

    public function deliver(array $state): bool
    {
        $sent = $this->notify('ad_schedule', $state);
        DB::table('publicidad_schedules')->where('agencia_id', $state['agencia_id'])
            ->where('revision', $state['revision'])->update(['pending' => !$sent]);
        return $sent;
    }

    public function retryPending(): void
    {
        $rows = DB::table('publicidad_schedules')->where('pending', true)->orderBy('agencia_id')->get();
        foreach ($rows as $row) $this->deliver(json_decode($row->payload, true));
    }

    public function notify(string $event, $data = null): bool
    {
        $url = config('services.publicidad.socket_url');
        if (!$url) return false;
        try {
            return Http::timeout(3)->post(rtrim($url, '/') . '/notify', compact('event', 'data'))->successful();
        } catch (\Exception $e) {
            Log::warning('Could not notify socket server: ' . $e->getMessage());
            return false;
        }
    }
}
