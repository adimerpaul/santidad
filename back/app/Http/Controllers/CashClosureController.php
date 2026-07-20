<?php

namespace App\Http\Controllers;

use App\Models\CashClosure;
use App\Models\Sales;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CashClosureController extends Controller
{
    /**
     * Get the current shift status and system amounts.
     */
    public function currentStatus(Request $request)
    {
        $user = $request->user();
        $agenciaId = $user->agencia_id;

        if (!$agenciaId) {
            return response()->json(['message' => 'El usuario no tiene una agencia asignada.'], 400);
        }

        // 1. Check if the current user has a pending shift to close
        $pendingShift = CashClosure::where('user_id', $user->id)
            ->where('estado', 'PENDIENTE')
            ->first();

        if ($pendingShift) {
            return response()->json([
                'status' => 'PENDIENTE',
                'id' => $pendingShift->id,
                'agencia_id' => $pendingShift->agencia_id,
                'agencia_nombre' => $pendingShift->agencia ? $pendingShift->agencia->nombre : 'Sin nombre',
                'responsable' => $pendingShift->user ? $pendingShift->user->name : $user->name,
                'fecha_apertura' => Carbon::parse($pendingShift->fecha_apertura)->format('Y-m-d\TH:i'),
                'observaciones_apertura' => $pendingShift->observaciones_apertura,
            ]);
        }

        // 2. Check if there is an active open shift in the branch
        $openShift = CashClosure::where('agencia_id', $agenciaId)
            ->where('estado', 'ABIERTO')
            ->first();

        if (!$openShift) {
            return response()->json([
                'status' => 'CERRADO',
                'agencia_nombre' => $user->agencia ? $user->agencia->nombre : 'Sin nombre',
                'responsable' => $user->name,
                'fecha_apertura' => Carbon::now()->format('Y-m-d\TH:i'), // Pre-filled default opening date
            ]);
        }

        // Calculate shift bounds and sales
        $startTime = Carbon::parse($openShift->fecha_apertura);
        $endTime = Carbon::now();

        // Fetch sales inside this timeframe
        $sales = Sales::where('agencia_id', $agenciaId)
            ->where('tipoVenta', 'Ingreso')
            ->where('estado', 'ACTIVO')
            ->where('created_at', '>=', $startTime)
            ->where('created_at', '<=', $endTime)
            ->get();

        $totalEfectivo = 0.0;
        $totalDigital = 0.0;

        foreach ($sales as $sale) {
            if ($sale->metodoPago === 'Efectivo') {
                $totalEfectivo += (float) ($sale->montoTotal ?? 0);
            } elseif ($sale->metodoPago === 'Personalizado') {
                $totalEfectivo += (float) ($sale->montoEfectivo ?? 0);
                $totalDigital += (float) ($sale->montoQr ?? 0);
            } else {
                $totalDigital += (float) ($sale->montoTotal ?? 0);
            }
        }

        $totalSistema = $totalEfectivo + $totalDigital;
        $isAdmin = (string)$user->id === '1';

        $response = [
            'status' => 'ABIERTO',
            'id' => $openShift->id,
            'agencia_id' => $agenciaId,
            'agencia_nombre' => $user->agencia ? $user->agencia->nombre : 'Sin nombre',
            'responsable' => $openShift->user ? $openShift->user->name : $user->name,
            'fecha_apertura' => $startTime->format('Y-m-d\TH:i'),
            'fecha_cierre' => $endTime->format('Y-m-d\TH:i'),
            'observaciones_apertura' => $openShift->observaciones_apertura,
        ];

        // System details only visible to Admin
        if ($isAdmin) {
            $response['monto_sistema_efectivo'] = round((float)$totalEfectivo, 2);
            $response['monto_sistema_digital'] = round((float)$totalDigital, 2);
            $response['monto_sistema_total'] = round((float)$totalSistema, 2);
        }

        return response()->json($response);
    }

    /**
     * Start a new shift (Apertura de Caja).
     */
    public function open(Request $request)
    {
        $user = $request->user();
        $agenciaId = $user->agencia_id;

        if (!$agenciaId) {
            return response()->json(['message' => 'El usuario no tiene una agencia asignada.'], 400);
        }

        // 1. Block opening if the current user has a pending shift
        $pendingShift = CashClosure::where('user_id', $user->id)
            ->where('estado', 'PENDIENTE')
            ->first();

        if ($pendingShift) {
            return response()->json(['message' => 'Debe finalizar su cierre de caja pendiente antes de abrir un nuevo turno.'], 400);
        }

        // 2. Check if there is an active open shift in the branch
        $existingOpen = CashClosure::where('agencia_id', $agenciaId)
            ->where('estado', 'ABIERTO')
            ->first();

        if ($existingOpen) {
            return response()->json(['message' => 'La caja ya se encuentra abierta para esta sucursal.'], 400);
        }

        $request->validate([
            'observaciones_apertura' => 'nullable|string',
            'fecha_apertura' => 'required|date',
        ]);

        $fechaApe = Carbon::parse($request->fecha_apertura);

        $closure = CashClosure::create([
            'agencia_id' => $agenciaId,
            'user_id' => $user->id,
            'fecha_apertura' => $fechaApe,
            'estado' => 'ABIERTO',
            'observaciones_apertura' => $request->observaciones_apertura,
        ]);

        return response()->json($closure);
    }

    /**
     * Close an active shift (Cierre de Caja).
     */
    public function close(Request $request)
    {
        $isFast = $request->boolean('is_fast', false);

        if (!$isFast) {
            $request->validate([
                'monto_fisico' => 'required|numeric|min:0',
                'observaciones' => 'nullable|string',
            ]);
        } else {
            $request->validate([
                'observaciones' => 'nullable|string',
            ]);
        }

        $user = $request->user();
        $agenciaId = $user->agencia_id;

        if (!$agenciaId) {
            return response()->json(['message' => 'El usuario no tiene una agencia asignada.'], 400);
        }

        // Find active open shift
        $openShift = CashClosure::where('agencia_id', $agenciaId)
            ->where('estado', 'ABIERTO')
            ->first();

        if (!$openShift) {
            return response()->json(['message' => 'No existe un turno abierto para cerrar.'], 400);
        }

        $startTime = Carbon::parse($openShift->fecha_apertura);
        $endTime = Carbon::now();

        // Calculate shift sales
        $sales = Sales::where('agencia_id', $agenciaId)
            ->where('tipoVenta', 'Ingreso')
            ->where('estado', 'ACTIVO')
            ->where('created_at', '>=', $startTime)
            ->where('created_at', '<=', $endTime)
            ->get();

        $totalEfectivo = 0.0;
        $totalDigital = 0.0;

        foreach ($sales as $sale) {
            if ($sale->metodoPago === 'Efectivo') {
                $totalEfectivo += (float) ($sale->montoTotal ?? 0);
            } elseif ($sale->metodoPago === 'Personalizado') {
                $totalEfectivo += (float) ($sale->montoEfectivo ?? 0);
                $totalDigital += (float) ($sale->montoQr ?? 0);
            } else {
                $totalDigital += (float) ($sale->montoTotal ?? 0);
            }
        }

        $totalSistema = $totalEfectivo + $totalDigital;

        if (!$isFast) {
            $montoFisico = (float) $request->monto_fisico;
            $diferencia = $montoFisico - $totalEfectivo;

            // Save closure details and mark closed
            $openShift->update([
                'fecha_cierre' => $endTime,
                'closed_by_user_id' => $user->id,
                'monto_fisico' => $montoFisico,
                'monto_sistema_efectivo' => $totalEfectivo,
                'monto_sistema_digital' => $totalDigital,
                'monto_sistema_total' => $totalSistema,
                'diferencia' => $diferencia,
                'observaciones' => $request->observaciones,
                'estado' => 'CERRADO',
            ]);
        } else {
            // Cierre Rápido (Relevo)
            $openShift->update([
                'fecha_cierre' => $endTime,
                'closed_by_user_id' => $user->id,
                'monto_sistema_efectivo' => $totalEfectivo,
                'monto_sistema_digital' => $totalDigital,
                'monto_sistema_total' => $totalSistema,
                'observaciones' => $request->observaciones,
                'estado' => 'PENDIENTE',
            ]);
        }

        return response()->json($openShift);
    }

    /**
     * Finalize a fast/pending shift closure by providing the physical amount.
     */
    public function confirmPending(Request $request, $id)
    {
        $user = $request->user();
        $shift = CashClosure::findOrFail($id);

        if ($shift->estado !== 'PENDIENTE') {
            return response()->json(['message' => 'Este turno no se encuentra en estado pendiente de arqueo.'], 400);
        }

        // Restrict to the owner or admin
        if ((string)$shift->user_id !== (string)$user->id && (string)$user->id !== '1') {
            return response()->json(['message' => 'No tiene permisos para finalizar este cierre.'], 403);
        }

        $request->validate([
            'monto_fisico' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        $montoFisico = (float)$request->monto_fisico;
        $totalEfectivo = (float)($shift->monto_sistema_efectivo ?? 0);
        $diferencia = $montoFisico - $totalEfectivo;

        $updateData = [
            'monto_fisico' => $montoFisico,
            'diferencia' => round($diferencia, 2),
            'estado' => 'CERRADO',
        ];

        if ($request->filled('observaciones')) {
            $updateData['observaciones'] = $shift->observaciones
                ? $shift->observaciones . ' | ' . $request->observaciones
                : $request->observaciones;
        }

        $shift->update($updateData);

        return response()->json($shift);
    }

    /**
     * Get closures history list.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = (string)$user->id === '1';

        $query = CashClosure::with(['user', 'closedByUser', 'agencia'])
            ->orderByRaw('CASE WHEN estado = "ABIERTO" THEN 0 ELSE 1 END')
            ->orderBy('fecha_apertura', 'desc');

        // Non-admin can only see their own sucursal closures
        if (!$isAdmin) {
            $query->where('agencia_id', $user->agencia_id);
        } else {
            // Admin can filter by agency
            if ($request->has('agencia_id') && $request->agencia_id != '') {
                $query->where('agencia_id', $request->agencia_id);
            }
        }

        // Limit results to keep queries highly responsive
        $closures = $query->limit(200)->get();

        // Strip system amounts for non-admins if they somehow query
        if (!$isAdmin) {
            $closures->transform(function ($item) {
                unset($item->monto_sistema_efectivo);
                unset($item->monto_sistema_digital);
                unset($item->monto_sistema_total);
                unset($item->diferencia);
                return $item;
            });
        }

        return response()->json($closures);
    }

    /**
     * Detect daily closures gaps for the last 15 days.
     */
    public function gaps(Request $request)
    {
        $user = $request->user();
        $isAdmin = (string)$user->id === '1';

        $agenciaId = $request->query('agencia_id');
        if (empty($agenciaId)) {
            $agenciaId = $user->agencia_id;
        }
        if (!$isAdmin) {
            $agenciaId = $user->agencia_id;
        }

        if (!$agenciaId) {
            return response()->json([]);
        }

        // Define search range (last 15 days)
        $startDate = Carbon::now()->subDays(14)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // 1. Fetch sales count grouped by date in a single query using indexes
        $salesCounts = Sales::where('agencia_id', $agenciaId)
            ->where('tipoVenta', 'Ingreso')
            ->where('estado', 'ACTIVO')
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupByRaw('DATE(created_at)')
            ->pluck('count', 'date')
            ->toArray();

        // 2. Fetch closures in the search range with user relations
        $closures = CashClosure::with(['user', 'closedByUser'])
            ->where('agencia_id', $agenciaId)
            ->where('fecha_cierre', '>=', $startDate)
            ->where('fecha_cierre', '<=', $endDate)
            ->get();

        $closuresByDate = [];
        foreach ($closures as $closure) {
            $dateKey = Carbon::parse($closure->fecha_cierre)->format('Y-m-d');
            $closuresByDate[$dateKey][] = [
                'closed_by' => $closure->closedByUser->name ?? ($closure->user->name ?? 'N/A'),
                'monto_fisico' => (float)$closure->monto_fisico
            ];
        }

        $gaps = [];
        for ($i = 14; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $salesCount = $salesCounts[$date] ?? 0;
            $dayClosures = $closuresByDate[$date] ?? [];

            $gaps[] = [
                'date' => $date,
                'has_sales' => $salesCount > 0,
                'has_closure' => count($dayClosures) > 0,
                'sin_cierre' => ($salesCount > 0 && count($dayClosures) == 0),
                'closures' => $dayClosures
            ];
        }

        return response()->json($gaps);
    }
}
