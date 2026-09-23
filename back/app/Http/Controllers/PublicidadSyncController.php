<?php

namespace App\Http\Controllers;

use App\Services\PublicidadSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicidadSyncController extends Controller
{
    public function __construct(private PublicidadSyncService $sync)
    {
    }

    public function show(Request $request)
    {
        $input = $request->validate(['agencia_id' => 'nullable|integer|min:1']);
        $agencia = isset($input['agencia_id']) ? (int) $input['agencia_id'] : null;

        // El manifiesto sale de caché; solo la hora del servidor se calcula en cada petición
        return response()->json(array_merge($this->sync->manifest($agencia), [
            'server_time_ms' => (int) floor(microtime(true) * 1000),
        ]))->header('Cache-Control', 'no-store, private');
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
        $ads = $this->sync->ads($agencia)->keyBy('id');
        $updated = 0;
        foreach ($input['items'] as $item) {
            $ad = $ads->get($item['id']);
            if (!$ad || $ad->type !== 'video' || $this->sync->mediaVersion($ad) !== $item['media_version']) {
                continue;
            }
            // The first successful measurement wins atomically. Do not touch updated_at:
            // learning a duration must not invalidate the already downloaded media.
            $updated += DB::table('publicidads')->where('id', $ad->id)
                ->where('updated_at', $ad->getRawOriginal('updated_at'))
                ->whereNull('duration_ms')->update(['duration_ms' => $item['duration_ms']]);
        }
        // Las demás pantallas esperan esta duración para activar el manifiesto: se les avisa
        if ($updated > 0) {
            $this->sync->changed(['agencia_id' => $agencia]);
        }
        return response()->json(['success' => true])->header('Cache-Control', 'no-store');
    }
}
