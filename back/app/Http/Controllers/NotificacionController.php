<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index($agenciaId)
    {
        return Notificacion::where('agencia_id', $agenciaId)
            ->where('leida', false)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function marcarComoLeida($id)
    {
        $notif = Notificacion::findOrFail($id);
        $notif->leida = true;
        $notif->save();
        return response()->json(['message' => 'Notificación marcada como leída']);
    }
}
