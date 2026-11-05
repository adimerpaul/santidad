<?php

namespace App\Http\Controllers;

use App\Models\CashClosure;
use App\Models\Sales;
use App\Models\User;
use App\Models\Agencia;
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

        // 2. Check if the current user has an active open shift
        $openShift = CashClosure::where('agencia_id', $agenciaId)
            ->where('user_id', $user->id)
            ->where('estado', 'ABIERTO')
            ->first();

        if ($openShift) {
            // Calculate shift bounds and sales for this user
            $startTime = Carbon::parse($openShift->fecha_apertura);
            $endTime = Carbon::now();

            $totalEfectivo = 0.0;
            $totalDigital = 0.0;
            $isAdmin = (string)$user->id === '1';

            // Los montos del sistema solo se exponen al admin (ver más abajo), así que
            // para el resto ni siquiera se consultan las ventas del turno: antes se
            // cargaban todas en memoria y se sumaban en PHP para luego descartarlas.
            if ($isAdmin) {
                $totales = Sales::where('agencia_id', $agenciaId)
                    ->where('user_id', $user->id)
                    ->where('tipoVenta', 'Ingreso')
                    ->where('estado', 'ACTIVO')
                    ->where('created_at', '>=', $startTime)
                    ->where('created_at', '<=', $endTime)
                    ->selectRaw("
                        COALESCE(SUM(CASE
                            WHEN metodoPago = 'Efectivo' THEN montoTotal
                            WHEN metodoPago = 'Personalizado' THEN montoEfectivo
                            ELSE 0
                        END), 0) as total_efectivo,
                        COALESCE(SUM(CASE
                            WHEN metodoPago = 'Efectivo' THEN 0
                            WHEN metodoPago = 'Personalizado' THEN montoQr
                            ELSE montoTotal
                        END), 0) as total_digital
                    ")
                    ->first();

                $totalEfectivo = (float) ($totales->total_efectivo ?? 0);
                $totalDigital = (float) ($totales->total_digital ?? 0);
            }

            $totalSistema = $totalEfectivo + $totalDigital;

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

        return response()->json([
            'status' => 'CERRADO',
            'agencia_nombre' => $user->agencia ? $user->agencia->nombre : 'Sin nombre',
            'responsable' => $user->name,
            'fecha_apertura' => Carbon::now()->format('Y-m-d\TH:i'),
        ]);

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

        // Check if this user already has an active open shift
        $existingOpen = CashClosure::where('agencia_id', $agenciaId)
            ->where('user_id', $user->id)
            ->where('estado', 'ABIERTO')
            ->first();

        if ($existingOpen) {
            return response()->json(['message' => 'Usted ya se encuentra con un turno abierto.'], 400);
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

        $this->notifySocket('caja_estado', [
            'agencia_id' => $agenciaId,
            'estado' => 'ABIERTO',
            'user_id' => $user->id,
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

        $closureId = $request->input('closure_id');
        $openShift = null;

        if ($closureId) {
            $openShift = CashClosure::where('id', $closureId)
                ->where('agencia_id', $agenciaId)
                ->first();

            if (!$openShift) {
                return response()->json(['message' => 'No existe el turno de caja especificado.'], 400);
            }
            if ($openShift->estado === 'CERRADO') {
                return response()->json(['message' => 'Este turno de caja ya fue finalizado.'], 400);
            }
        } else {
            // Check if user has a pending shift waiting for physical cash amount
            $pendingShift = CashClosure::where('user_id', $user->id)
                ->where('agencia_id', $agenciaId)
                ->where('estado', 'PENDIENTE')
                ->first();

            if ($pendingShift && !$isFast) {
                $openShift = $pendingShift;
            } else {
                // Fallback: Find active open shift for this user (or agency if admin)
                $query = CashClosure::where('agencia_id', $agenciaId)->where('estado', 'ABIERTO');
                if ((string)$user->id !== '1') {
                    $query->where('user_id', $user->id);
                }
                $openShift = $query->first();

                if (!$openShift) {
                    return response()->json(['message' => 'No tiene un turno abierto a su nombre para cerrar.'], 400);
                }
            }
        }

        $startTime = Carbon::parse($openShift->fecha_apertura);
        $endTime = Carbon::now();

        // Calculate shift sales for this shift's user
        $sales = Sales::where('agencia_id', $agenciaId)
            ->where('user_id', $openShift->user_id)
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

        $this->notifySocket('caja_estado', [
            'agencia_id' => $agenciaId,
            'estado' => $openShift->estado,
            'user_id' => $user->id,
        ]);

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

        $this->notifySocket('caja_estado', [
            'agencia_id' => $shift->agencia_id,
            'estado' => 'CERRADO',
            'user_id' => $user->id,
        ]);

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

    /** Options are limited to accounts with shifts visible to the requesting user. */
    public function reportOptions(Request $request)
    {
        $user = $request->user();
        $isAdmin = (string) $user->id === '1';
        $scope = CashClosure::query();
        if (!$isAdmin) {
            $scope->where('agencia_id', $user->agencia_id ?? 0);
        }
        $pairs = $scope->select('user_id', 'agencia_id')->distinct()->get();
        $accounts = User::whereIn('id', $pairs->pluck('user_id')->unique())
            ->orderBy('name')->get(['id', 'name'])->map(function ($account) use ($pairs) {
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'agencia_ids' => $pairs->where('user_id', $account->id)->pluck('agencia_id')->values(),
                ];
            });
        $agencies = Agencia::query()->when(!$isAdmin, function ($query) use ($user) {
            $query->where('id', $user->agencia_id ?? 0);
        })->orderBy('nombre')->get(['id', 'nombre']);

        return response()->json(['agencias' => $agencies, 'cuentas' => $accounts])
            ->header('Cache-Control', 'no-store');
    }

    /** Read-only history. Summary always covers the entire scope, not only the current page. */
    public function report(Request $request)
    {
        $filters = $request->validate([
            'agencia_id' => 'nullable|integer|min:1',
            'user_id' => 'nullable|integer|min:1',
            'desde' => 'nullable|date_format:Y-m-d',
            'hasta' => 'nullable|date_format:Y-m-d'.($request->filled('desde') ? '|after_or_equal:desde' : ''),
            'vista' => 'nullable|in:todos,anomalias,faltantes,sobrantes,pendientes,abiertos,cerrados',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|in:15,30,50',
        ]);
        $user = $request->user();
        $isAdmin = (string) $user->id === '1';
        $query = CashClosure::query();
        if (!$isAdmin) {
            $query->where('agencia_id', $user->agencia_id ?? 0);
        } elseif (!empty($filters['agencia_id'])) {
            $query->where('agencia_id', $filters['agencia_id']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        // Closed/pending shifts belong to their closing date; open shifts to their opening date.
        if (!empty($filters['desde'])) {
            $query->whereRaw('COALESCE(fecha_cierre, fecha_apertura) >= ?', [$filters['desde'].' 00:00:00']);
        }
        if (!empty($filters['hasta'])) {
            $query->whereRaw('COALESCE(fecha_cierre, fecha_apertura) < ?', [
                Carbon::parse($filters['hasta'])->addDay()->format('Y-m-d').' 00:00:00',
            ]);
        }
        // Stored money uses two decimals. ROUND prevents floating-point noise at exact thresholds.
        $shortage = "estado = 'CERRADO' AND ROUND(diferencia, 2) < -20";
        $surplus = "estado = 'CERRADO' AND ROUND(diferencia, 2) > 150";
        $summaryQuery = (clone $query)->selectRaw("COUNT(*) as turnos,
            SUM(CASE WHEN estado = 'CERRADO' THEN 1 ELSE 0 END) as cerrados,
            SUM(CASE WHEN estado = 'PENDIENTE' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado = 'ABIERTO' THEN 1 ELSE 0 END) as abiertos");
        if ($isAdmin) {
            $summaryQuery->selectRaw("SUM(CASE WHEN $shortage THEN 1 ELSE 0 END) as faltantes,
                SUM(CASE WHEN $surplus THEN 1 ELSE 0 END) as sobrantes,
                SUM(CASE WHEN $shortage THEN -diferencia ELSE 0 END) as monto_faltante,
                SUM(CASE WHEN $surplus THEN diferencia ELSE 0 END) as monto_sobrante");
        }
        $summary = $summaryQuery->first()->getAttributes();
        foreach (['turnos', 'cerrados', 'pendientes', 'abiertos', 'faltantes', 'sobrantes'] as $field) {
            if (array_key_exists($field, $summary)) $summary[$field] = (int) $summary[$field];
        }
        if ($isAdmin) {
            $summary['anomalias'] = $summary['faltantes'] + $summary['sobrantes'];
            $summary['monto_faltante'] = round((float) $summary['monto_faltante'], 2);
            $summary['monto_sobrante'] = round((float) $summary['monto_sobrante'], 2);
        }
        $view = $filters['vista'] ?? 'todos';
        if (in_array($view, ['anomalias', 'faltantes', 'sobrantes'], true)) {
            abort_unless($isAdmin, 403, 'Solo el administrador puede consultar diferencias de caja.');
            if ($view === 'anomalias') $query->whereRaw("(($shortage) OR ($surplus))");
            else $query->whereRaw($view === 'faltantes' ? $shortage : $surplus);
        } elseif (in_array($view, ['pendientes', 'abiertos', 'cerrados'], true)) {
            $query->where('estado', ['pendientes' => 'PENDIENTE', 'abiertos' => 'ABIERTO', 'cerrados' => 'CERRADO'][$view]);
        }
        $closures = $query->with(['user:id,name', 'closedByUser:id,name', 'agencia:id,nombre'])
            ->orderByRaw('COALESCE(fecha_cierre, fecha_apertura) DESC')->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 15);
        if (!$isAdmin) {
            $closures->getCollection()->each(function ($item) {
                $item->makeHidden(['monto_sistema_efectivo', 'monto_sistema_digital', 'monto_sistema_total', 'diferencia']);
            });
        }
        return response()->json(['resumen' => $summary, 'cierres' => $closures])
            ->header('Cache-Control', 'no-store');
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

    private function notifySocket($event, $data)
    {
        try {
            \Illuminate\Support\Facades\Http::post(env('SOCKET_SERVER_URL') . '/notify', [
                'event' => $event,
                'data'  => $data
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Could not notify socket server: ' . $e->getMessage());
        }
    }
}
