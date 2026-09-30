<?php

namespace App\Http\Controllers;

use App\Models\Publicidad;
use App\Services\PublicidadSyncService;
use Illuminate\Http\Request;

class PublicidadSyncController extends Controller
{
    public function show(Request $request, PublicidadSyncService $service)
    {
        $data = $request->validate(['agencia_id' => 'required|integer|exists:agencias,id']);
        $state = $service->snapshot((int) $data['agencia_id']);
        // Bootstrap a socket whose local state was lost, without trusting client data.
        $service->deliver($state);
        $state['server_time_ms'] = (int) round(microtime(true) * 1000);
        return response()->json($state)->header('Cache-Control', 'no-store');
    }

    public function durations(Request $request, PublicidadSyncService $service)
    {
        $data = $request->validate([
            'agencia_id' => 'required|integer|exists:agencias,id',
            'items' => 'required|array|max:100',
            'items.*.id' => 'required|integer',
            'items.*.media_version' => 'required|string|max:64',
            'items.*.duration_ms' => 'required|integer|min:100|max:86400000',
        ]);
        $changed = false;
        foreach ($data['items'] as $item) {
            $ad = Publicidad::where('active', true)->where('type', 'video')
                ->where(fn ($q) => $q->whereNull('agencia_id')->orWhere('agencia_id', $data['agencia_id']))
                ->find($item['id']);
            if (!$ad || $service->mediaVersion($ad) !== $item['media_version']) continue;
            // First valid measurement wins; do not alter the media version/updated_at.
            $changed = (bool) \DB::table('publicidads')->where('id', $ad->id)
                ->whereNull('duration_ms')->update(['duration_ms' => $item['duration_ms']]) || $changed;
        }
        if ($changed) $service->publish();
        return response()->json(['success' => true]);
    }
}
