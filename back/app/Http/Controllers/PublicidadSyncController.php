<?php

namespace App\Http\Controllers;

use App\Models\Publicidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicidadSyncController extends Controller
{
    private function ads(?int $agencia)
    {
        return Publicidad::where('active', true)->where(function ($query) use ($agencia) {
            $query->whereNull('agencia_id');
            if ($agencia) {
                $query->orWhere('agencia_id', $agencia);
            }
        })->orderByDesc('id')->get();
    }

    private function mediaVersion(Publicidad $ad): string
    {
        return hash('sha256', json_encode([
            $ad->id, $ad->file_id, $ad->url, (string) $ad->updated_at,
        ]));
    }

    public function show(Request $request)
    {
        $input = $request->validate(['agencia_id' => 'nullable|integer|min:1']);
        $agencia = isset($input['agencia_id']) ? (int) $input['agencia_id'] : null;
        $items = $this->ads($agencia)->map(function ($ad) {
            return array_merge($ad->toArray(), [
                'media_version' => $this->mediaVersion($ad),
                'duration_ms' => $ad->type === 'video' ? (int) $ad->duration_ms : 10000,
            ]);
        })->values();

        // A fixed epoch makes the cycle deterministic, including after server restarts.
        // Durations are canonical: every player must use the values returned here.
        return response()->json([
            'protocol' => 1,
            'agencia_id' => $agencia,
            'epoch_ms' => 0,
            'version' => hash('sha256', json_encode([$agencia, $items->map(fn ($ad) => [
                $ad['media_version'], $ad['duration_ms'],
            ])->all()])),
            'ready' => $items->every(fn ($ad) => $ad['duration_ms'] > 0),
            'items' => $items,
            'server_time_ms' => (int) floor(microtime(true) * 1000),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function durations(Request $request)
    {
        $input = $request->validate([
            'agencia_id' => 'nullable|integer|min:1',
            'items' => 'required|array|min:1|max:100',
            'items.*.id' => 'required|integer|min:1',
            'items.*.media_version' => 'required|string|size:64',
            'items.*.duration_ms' => 'required|integer|min:1|max:86400000',
        ]);
        $agencia = isset($input['agencia_id']) ? (int) $input['agencia_id'] : null;
        $ads = $this->ads($agencia)->keyBy('id');
        foreach ($input['items'] as $item) {
            $ad = $ads->get($item['id']);
            if (!$ad || $ad->type !== 'video' || $this->mediaVersion($ad) !== $item['media_version']) {
                continue;
            }
            // The first successful measurement wins atomically. Do not touch updated_at:
            // learning a duration must not invalidate the already downloaded media.
            DB::table('publicidads')->where('id', $ad->id)
                ->where('updated_at', $ad->getRawOriginal('updated_at'))
                ->whereNull('duration_ms')->update(['duration_ms' => $item['duration_ms']]);
        }
        return response()->json(['success' => true])->header('Cache-Control', 'no-store');
    }
}
