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

        // Check if there is an active open shift
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

        // 2. Fetch sales inside this timeframe
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

        // Check if there is an active open shift
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
        if ($fechaApe->isFuture()) {
            return response()->json(['message' => 'La fecha de apertura no puede ser una fecha futura.'], 400);
        }

        // Validate that fecha_apertura is not earlier than the last closed shift's fecha_cierre
        $lastClosure = CashClosure::where('agencia_id', $agenciaId)
            ->where('estado', 'CERRADO')
            ->orderBy('fecha_cierre', 'desc')
            ->first();
        if ($lastClosure && $fechaApe->lt(Carbon::parse($lastClosure->fecha_cierre))) {
            return response()->json(['message' => 'La fecha de apertura no puede ser anterior al último cierre de caja (' . Carbon::parse($lastClosure->fecha_cierre)->format('d/m/Y H:i') . ').'], 400);
        }

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
        $request->validate([
            'monto_fisico' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

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

        return response()->json($openShift);
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
