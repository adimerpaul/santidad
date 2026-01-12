<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index(Request $request, $agenciaId)
    {
        // 1. Obtener las notificaciones paginadas (15 por página)
        $notificaciones = Notificacion::where('agencia_id', $agenciaId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // 2. Contar las no leídas por separado (para el badge rojo)
        // Esto es mucho más rápido que cargar todas las filas
        $noLeidas = Notificacion::where('agencia_id', $agenciaId)
            ->where('leida', false)
            ->count();

        return response()->json([
            'listado' => $notificaciones,
            'total_no_leidas' => $noLeidas
        ]);
    }

    public function marcarComoLeida($id)
    {
        $notif = Notificacion::findOrFail($id);
        $notif->leida = true;
        $notif->save();
        return response()->json(['message' => 'Notificación marcada como leída']);
    }
}