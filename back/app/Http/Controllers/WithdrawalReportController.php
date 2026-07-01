<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalReport;
use App\Models\WithdrawalItem;
use App\Models\Product;
use App\Models\Buy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WithdrawalReportController extends Controller
{
    public function index(Request $request)
    {
        $query = WithdrawalReport::with(['agencia', 'user', 'items.product', 'items.buy.proveedor', 'items.agencia', 'items.user']);
        $user = $request->user();

        if ($user->id !== 1) {
            $query->where('agencia_id', $user->agencia_id);
        } elseif ($request->filled('agencia_id')) {
            $agencias = is_array($request->agencia_id) ? $request->agencia_id : explode(',', $request->agencia_id);
            $agencias = array_filter($agencias, function ($val) {
                return $val !== null && $val !== '' && $val !== 'null';
            });
            if (!empty($agencias)) {
                $query->whereIn('agencia_id', $agencias);
            }
        }

        if ($request->filled('mes')) {
            $query->where('mes', $request->mes);
        }

        if ($request->filled('anio')) {
            $query->where('anio', $request->anio);
        }

        if ($request->filled('tipo')) {
            if ($request->tipo === 'VENCIMIENTO/DEVOLUCION') {
                $query->whereIn('tipo', ['VENCIMIENTO/DEVOLUCION', 'VENCIMIENTO', 'DEVOLUCION', 'VENCIDOS/DEVOLUCIONES']);
            } else {
                $query->where('tipo', $request->tipo);
            }
        }

        $rowsPerPage = $request->input('rowsPerPage', 20);
        if ($rowsPerPage <= 0) {
            $rowsPerPage = $query->count() ?: 20;
        }

        return $query->orderBy('anio', 'desc')->orderBy('mes', 'desc')->paginate($rowsPerPage);
    }

    public function allItems(Request $request)
    {
        $user = $request->user();
        $query = WithdrawalItem::with(['report.agencia', 'report.user', 'product', 'buy.proveedor', 'agencia'])
            ->whereHas('report', function ($q) use ($request, $user) {
                if ($user->id !== 1) {
                    $q->where('agencia_id', $user->agencia_id);
                } elseif ($request->filled('agencia_id')) {
                    $agencias = is_array($request->agencia_id) ? $request->agencia_id : explode(',', $request->agencia_id);
                    $agencias = array_filter($agencias, function ($val) {
                        return $val !== null && $val !== '' && $val !== 'null';
                    });
                    if (!empty($agencias)) {
                        $q->whereIn('agencia_id', $agencias);
                    }
                }

                if ($request->filled('mes')) {
                    $q->where('mes', $request->mes);
                }
                if ($request->filled('anio')) {
                    $q->where('anio', $request->anio);
                }
            });

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $rowsPerPage = $request->input('rowsPerPage', 20);
        if ($rowsPerPage <= 0) {
            $rowsPerPage = $query->count() ?: 20;
        }

        return $query->orderBy('id', 'desc')->paginate($rowsPerPage);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'agencia_id' => 'required|exists:agencias,id',
            'mes' => 'required|integer|between:1,12',
            'anio' => 'required|integer',
            'tipo' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ]);

        $agencia_id = $request->agencia_id;
        if ($user->id !== 1) {
            $agencia_id = $user->agencia_id;
        }

        $report = WithdrawalReport::create([
            'agencia_id' => $agencia_id,
            'mes' => $request->mes,
            'anio' => $request->anio,
            'user_id' => $user->id,
            'estado' => 'ABIERTO',
            'tipo' => $request->input('tipo', 'VENCIMIENTO'),
            'observaciones' => $request->observaciones,
        ]);

        return response()->json($report->load('agencia', 'user'));
    }

    public function show($id)
    {
        $user = auth()->user();
        $report = WithdrawalReport::with(['agencia', 'user', 'items.product', 'items.buy.proveedor', 'items.buy.agencia', 'items.agencia', 'items.user'])->findOrFail($id);

        if ($user->id !== 1 && $report->agencia_id !== $user->agencia_id) {
            return response()->json(['message' => 'No tiene permiso para ver este informe.'], 403);
        }

        return response()->json($report);
    }

    public function close($id)
    {
        $user = auth()->user();
        $report = WithdrawalReport::with(['items.product', 'items.buy', 'items.user'])->findOrFail($id);
        
        if ($user->id !== 1) {
            return response()->json(['message' => 'Solo el administrador puede marcar como revisado.'], 403);
        }

        if ($report->estado === 'REVISADO') {
            return response()->json(['message' => 'El informe ya ha sido revisado.'], 422);
        }

        if ($report->estado !== 'PENDIENTE' && $report->estado !== 'OBSERVADO') {
            return response()->json(['message' => 'El informe debe estar en estado PENDIENTE u OBSERVADO para ser revisado.'], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($report->items as $item) {
                if ($item->estado === 'PENDIENTE' || $item->estado === 'OBSERVADO') {
                    throw new \Exception("El producto '{$item->product->nombre}' aún está pendiente de revisión o con observaciones.");
                }

                if ($item->estado === 'ACEPTADO' || $item->estado === 'SUBSANADO') {
                    $product = $item->product;
                    $actualAgenciaId = $item->agencia_id ?? ($item->buy->agencia_id ?? 0);

                    if (!$this->hasEnoughStock($product, $actualAgenciaId, $item->cantidad)) {
                        throw new \Exception("Stock insuficiente para el producto: {$product->nombre} en la sucursal seleccionada.");
                    }

                    $this->updateStock($product, $actualAgenciaId, $item->cantidad);
                }
            }

            $report->update(['estado' => 'REVISADO']);
            DB::commit();
            return response()->json($report->load('agencia', 'user', 'items.product', 'items.agencia', 'items.user'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al cerrar el informe: ' . $e->getMessage()], 422);
        }
    }

    public function send($id)
    {
        $user = auth()->user();
        $report = WithdrawalReport::findOrFail($id);

        if ($user->id !== 1 && $report->agencia_id !== $user->agencia_id) {
            return response()->json(['message' => 'No tiene permiso para enviar este informe.'], 403);
        }

        if ($report->estado !== 'ABIERTO' && $report->estado !== 'OBSERVADO') {
            return response()->json(['message' => 'Solo se pueden enviar informes que estén ABIERTOS u OBSERVADOS.'], 422);
        }

        $report->update(['estado' => 'PENDIENTE']);
        return response()->json($report->load('agencia', 'user'));
    }

    public function reopen($id)
    {
        $user = auth()->user();
        $report = WithdrawalReport::findOrFail($id);

        if ($user->id !== 1) {
            return response()->json(['message' => 'Solo el administrador puede reabrir informes.'], 403);
        }

        if ($report->estado === 'REVISADO') {
            return response()->json(['message' => 'No se puede reabrir un informe que ya ha sido REVISADO.'], 422);
        }

        $report->update(['estado' => 'ABIERTO']);
        return response()->json($report->load('agencia', 'user'));
    }

    public function destroy($id)
    {
        $report = WithdrawalReport::findOrFail($id);
        $user = auth()->user();

        if ($user->id !== 1 && $report->agencia_id !== $user->agencia_id) {
            return response()->json(['message' => 'No tiene permiso para eliminar este informe.'], 403);
        }
        
        if ($report->estado === 'REVISADO' && $user->id !== 1) {
            return response()->json(['message' => 'Solo el administrador puede eliminar informes revisados.'], 403);
        }
        
        DB::beginTransaction();
        try {
            if ($report->estado === 'REVISADO') {
                foreach ($report->items as $item) {
                    $actualAgenciaId = $item->agencia_id ?? ($item->buy->agencia_id ?? 0);
                    // To restore, we subtract the amount (reversing addition/withdrawal)
                    $this->updateStock($item->product, $actualAgenciaId, -$item->cantidad);
                }
            }
            
            $report->delete();
            DB::commit();
            return response()->json(['message' => 'Informe eliminado correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al eliminar el informe: ' . $e->getMessage()], 500);
        }
    }

    public function addItem(Request $request, $id)
    {
        $report = WithdrawalReport::findOrFail($id);
        $user = $request->user();

        if ($user->id !== 1 && $report->agencia_id !== $user->agencia_id) {
            return response()->json(['message' => 'No tiene permiso para agregar productos a este informe.'], 403);
        }

        if ($report->estado !== 'ABIERTO') {
            return response()->json(['message' => 'Solo se pueden agregar productos a un informe que esté ABIERTO.'], 422);
        }

        $request->validate([
            'buy_id' => 'required|exists:buys,id',
            'cantidad' => 'required|integer',
            'tipo' => 'required|string',
            'agencia_id' => 'nullable|integer',
            'stock_sistema' => 'nullable|integer',
            'conteo_fisico' => 'nullable|integer|min:0',
            'lote' => 'nullable|string',
        ]);

        if ($request->cantidad == 0) {
            return response()->json(['message' => 'La cantidad no puede ser cero.'], 422);
        }

        $buy = Buy::with('product')->findOrFail($request->buy_id);
        if ($request->filled('lote') && $request->lote !== $buy->lote) {
            $buy->update(['lote' => $request->lote]);
        }

        // Prevent duplicate buy_id in the same report
        $exists = WithdrawalItem::where('withdrawal_report_id', $report->id)
            ->where('buy_id', $buy->id)
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Este lote/compra ya se encuentra registrado en el informe. Edite el registro existente si desea modificar la cantidad.'], 422);
        }

        $product = $buy->product;
        $actualAgenciaId = $request->agencia_id ?? ($buy->agencia_id ?? 0);

        $cantidad = $request->cantidad;
        if ($report->tipo !== 'CONTEO FISICO') {
            $cantidad = -abs($cantidad);
        }

        if (!$this->hasEnoughStock($product, $actualAgenciaId, $cantidad)) {
            return response()->json(['message' => 'Stock insuficiente en la sucursal seleccionada.'], 422);
        }

        $item = WithdrawalItem::create([
            'withdrawal_report_id' => $report->id,
            'buy_id' => $buy->id,
            'product_id' => $buy->product_id,
            'agencia_id' => $request->agencia_id,
            'cantidad' => $cantidad,
            'stock_sistema' => $request->stock_sistema,
            'conteo_fisico' => $request->conteo_fisico,
            'tipo' => $request->tipo,
            'descripcion' => $request->descripcion,
            'user_id' => $user->id,
        ]);

        return response()->json($item->load('product', 'buy.proveedor', 'agencia', 'user'));
    }

    public function updateItem(Request $request, $reportId, $itemId)
    {
        $user = $request->user();
        $item = WithdrawalItem::where('withdrawal_report_id', $reportId)->findOrFail($itemId);
        $report = $item->report;

        $isReportOwner = ($report->agencia_id === $user->agencia_id);
        $isEditableReportState = ($report->estado === 'ABIERTO' || $report->estado === 'OBSERVADO');

        if ($user->id !== 1) {
            if (!$isReportOwner || !$isEditableReportState) {
                return response()->json(['message' => 'No tiene permiso para editar este ítem.'], 403);
            }
        }

        $request->validate([
            'cantidad' => 'required|integer',
            'tipo' => 'required|string',
            'agencia_id' => 'nullable|integer',
            'admin_descripcion' => 'nullable|string',
            'stock_sistema' => 'nullable|integer',
            'conteo_fisico' => 'nullable|integer|min:0',
            'estado' => 'nullable|string|in:PENDIENTE,ACEPTADO,RECHAZADO,PRORROGADO,OBSERVADO,SUBSANADO',
            'prorroga_hasta' => 'nullable|date_format:Y-m-d',
            'lote' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $this->trackChanges($item, $request);

            if ($request->filled('lote')) {
                $buy = Buy::findOrFail($item->buy_id);
                if ($request->lote !== $buy->lote) {
                    $buy->update(['lote' => $request->lote]);
                }
            }

            $cantidad = $request->cantidad;
            if ($report->tipo !== 'CONTEO FISICO') {
                $cantidad = -abs($cantidad);
            }

            $updateFields = ['tipo', 'agencia_id', 'admin_descripcion', 'stock_sistema', 'conteo_fisico'];
            if ($user->id === 1) {
                $updateFields[] = 'estado';
                $updateFields[] = 'prorroga_hasta';
            }

            $updateData = $request->only($updateFields);
            $updateData['cantidad'] = $cantidad;
            if ($user->id !== 1) {
                $updateData['user_id'] = $user->id;
            }

            // Stock validation for normal reports
            if ($report->tipo !== 'CONTEO FISICO') {
                $actualAgenciaId = $request->agencia_id ?? ($item->agencia_id ?? ($item->buy->agencia_id ?? 0));
                $product = Product::findOrFail($item->product_id);
                if (!$this->hasEnoughStock($product, $actualAgenciaId, $cantidad)) {
                    return response()->json(['message' => 'Stock insuficiente en la sucursal seleccionada.'], 422);
                }
            }

            if ($user->id !== 1) {
                if ($item->estado === 'OBSERVADO') {
                    $updateData['estado'] = 'SUBSANADO';
                }
            } else {
                if ($request->filled('estado') && $request->estado === 'OBSERVADO') {
                    $report->update(['estado' => 'OBSERVADO']);
                }
            }

            if ($report->estado === 'REVISADO') {
                $oldAgenciaId = $item->agencia_id ?? ($item->buy->agencia_id ?? 0);
                $this->updateStock($item->product, $oldAgenciaId, -$item->cantidad);

                $newAgenciaId = $request->agencia_id;
                $product = Product::findOrFail($item->product_id);
                if (!$this->hasEnoughStock($product, $newAgenciaId, $cantidad)) {
                     throw new \Exception("Stock insuficiente en la nueva sucursal seleccionada.");
                }

                $item->update($updateData);
                $this->updateStock($product, $newAgenciaId, $item->cantidad);
            } else {
                $item->update($updateData);
            }

            DB::commit();
            return response()->json($item->load('product', 'buy.proveedor', 'agencia', 'user'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar el ítem: ' . $e->getMessage()], 422);
        }
    }

    private function trackChanges($item, $request)
    {
        $changed = false;
        if ($item->cantidad != $request->cantidad && $item->cantidad_original === null) {
            $item->cantidad_original = $item->cantidad;
            $changed = true;
        }
        if ($item->agencia_id != $request->agencia_id && $item->agencia_id_original === null) {
            $item->agencia_id_original = $item->agencia_id;
            $changed = true;
        }
        if ($changed) {
            $item->save();
        }
    }

    public function removeItem($reportId, $itemId)
    {
        $report = WithdrawalReport::findOrFail($reportId);
        $user = auth()->user();

        if ($user->id !== 1 && $report->agencia_id !== $user->agencia_id) {
            return response()->json(['message' => 'No tiene permiso para quitar productos de este informe.'], 403);
        }

        if ($report->estado !== 'ABIERTO' && $report->estado !== 'OBSERVADO') {
            return response()->json(['message' => 'Solo se pueden quitar productos de un informe que esté ABIERTO u OBSERVADO.'], 422);
        }

        $item = WithdrawalItem::where('withdrawal_report_id', $reportId)->findOrFail($itemId);
        $item->delete();
        
        return response()->json(['message' => 'Producto quitado del informe.']);
    }

    private function hasEnoughStock($product, $agenciaId, $cantidad)
    {
        if ($cantidad >= 0) return true; 
        $field = $this->getAgenciaStockField($agenciaId);
        return $product->$field >= abs($cantidad);
    }

    private function updateStock($product, $agenciaId, $cantidad)
    {
        $field = $this->getAgenciaStockField($agenciaId);
        $product->$field += $cantidad;
        $product->cantidad += $cantidad; 
        $product->save();
    }

    private function getAgenciaStockField($agenciaId)
    {
        if ($agenciaId == 0) return 'cantidadAlmacen';
        return 'cantidadSucursal' . $agenciaId;
    }

    public function searchProducts(Request $request)
    {
        $search = $request->search;
        $user = $request->user();
        
        $agencia_id = $request->input('agencia_id');
        if ($user->id !== 1) {
            $agencia_id = $user->agencia_id;
        }

        $limit_lotes = $request->input('limit_lotes', '7');

        // We build the query for fetching buys
        $queryBuilder = Buy::query();

        if ($search) {
            $words = array_filter(explode(' ', $search));
            $queryBuilder->where(function($query) use ($words) {
                // Option A: Product name matches all words
                $query->where(function($q) use ($words) {
                    $q->whereHas('product', function($qp) use ($words) {
                        foreach ($words as $word) {
                            $upperWord = strtoupper($word);
                            $qp->whereRaw("UPPER(nombre) LIKE ?", ["%$upperWord%"]);
                        }
                    });
                })
                // Option B: Lote matches all words
                ->orWhere(function($q) use ($words) {
                    foreach ($words as $word) {
                        $upperWord = strtoupper($word);
                        $q->whereRaw("UPPER(lote) LIKE ?", ["%$upperWord%"]);
                    }
                })
                // Option C: Factura matches all words
                ->orWhere(function($q) use ($words) {
                    foreach ($words as $word) {
                        $upperWord = strtoupper($word);
                        $q->whereRaw("UPPER(factura) LIKE ?", ["%$upperWord%"]);
                    }
                });
            });
        }

        if ($agencia_id !== null && $agencia_id !== '') {
            if ($agencia_id == 1) {
                // Special case: Casa Matriz (1) includes Warehouse (null)
                $queryBuilder->where(function($q) {
                    $q->where('agencia_id', 1)
                      ->orWhereNull('agencia_id');
                });
            } else {
                $queryBuilder->where('agencia_id', $agencia_id);
            }
        }

        if ($limit_lotes !== 'all') {
            $limitValue = intval($limit_lotes);
            if ($limitValue <= 0) $limitValue = 3;

            // Get all matching product IDs
            $productIds = (clone $queryBuilder)->distinct()->pluck('product_id')->toArray();
            
            // For each product, get only the latest N buy IDs
            $buyIds = [];
            foreach ($productIds as $productId) {
                $latestBuyIds = Buy::where('product_id', $productId)
                    ->where(function($q) use ($agencia_id) {
                        if ($agencia_id !== null && $agencia_id !== '') {
                            if ($agencia_id == 1) {
                                $q->where('agencia_id', 1)->orWhereNull('agencia_id');
                            } else {
                                $q->where('agencia_id', $agencia_id);
                            }
                        }
                    })
                    ->orderBy('id', 'desc')
                    ->limit($limitValue)
                    ->pluck('id')
                    ->toArray();
                $buyIds = array_merge($buyIds, $latestBuyIds);
            }

            // Return only those buys, sorted by dateExpiry ascending
            return Buy::with(['product', 'proveedor', 'agencia', 'user'])
                ->whereIn('id', $buyIds)
                ->orderBy('dateExpiry', 'asc')
                ->paginate(50);
        }

        // Default / Show all: just return everything sorted by expiry date
        return $queryBuilder->with(['product', 'proveedor', 'agencia', 'user'])
            ->orderBy('dateExpiry', 'asc')
            ->paginate(50);
    }

    public function updateItemsBulk(Request $request, $reportId)
    {
        $user = $request->user();
        if ($user->id !== 1) {
            return response()->json(['message' => 'No tiene permiso para realizar esta acción.'], 403);
        }

        $report = WithdrawalReport::findOrFail($reportId);
        if ($report->estado === 'REVISADO') {
            return response()->json(['message' => 'No se puede modificar un informe que ya ha sido REVISADO.'], 422);
        }

        $request->validate([
            'estado' => 'required|string|in:PENDIENTE,ACEPTADO,RECHAZADO,PRORROGADO,OBSERVADO,SUBSANADO',
        ]);

        $estado = $request->estado;

        DB::beginTransaction();
        try {
            // Update all items in this report to the given state
            WithdrawalItem::where('withdrawal_report_id', $report->id)
                ->update(['estado' => $estado]);

            DB::commit();

            return response()->json([
                'message' => 'Ítems actualizados correctamente.',
                'items' => WithdrawalItem::where('withdrawal_report_id', $report->id)
                    ->with('product', 'buy.proveedor', 'agencia')
                    ->get()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar los ítems: ' . $e->getMessage()], 500);
        }
    }

    public function getCentralReturns(Request $request)
    {
        $user = $request->user();
        if ($user->id !== 1 && (int)$user->agencia_id !== 1) {
            return response()->json(['message' => 'No tiene permiso para ver las devoluciones a central.'], 403);
        }

        $query = WithdrawalItem::with([
            'report.agencia',
            'report.user',
            'product',
            'buy.proveedor',
            'agencia'
        ])->where('tipo', 'ENVIADO A CENTRAL PARA DEVOLUCION');

        if ($request->filled('agencia_id')) {
            $agencias = is_array($request->agencia_id) ? $request->agencia_id : explode(',', $request->agencia_id);
            $agencias = array_filter($agencias, function ($val) {
                return $val !== null && $val !== '' && $val !== 'null';
            });
            if (!empty($agencias)) {
                $query->whereHas('report', function ($q) use ($agencias) {
                    $q->whereIn('agencia_id', $agencias);
                });
            }
        }

        if ($request->filled('mes')) {
            $query->whereHas('report', function ($q) use ($request) {
                $q->where('mes', $request->mes);
            });
        }

        if ($request->filled('anio')) {
            $query->whereHas('report', function ($q) use ($request) {
                $q->where('anio', $request->anio);
            });
        }

        if ($request->filled('central_estado')) {
            $query->where('central_estado', $request->central_estado);
        }

        $rowsPerPage = $request->input('rowsPerPage', 20);
        if ($rowsPerPage <= 0) {
            $rowsPerPage = $query->count() ?: 20;
        }

        return $query->orderBy('id', 'desc')->paginate($rowsPerPage);
    }

    public function updateCentralReturn(Request $request, $itemId)
    {
        $user = $request->user();
        if ($user->id !== 1 && (int)$user->agencia_id !== 1) {
            return response()->json(['message' => 'No tiene permiso para modificar las devoluciones a central.'], 403);
        }

        $item = WithdrawalItem::findOrFail($itemId);

        $request->validate([
            'central_estado' => 'required|string|in:PENDIENTE,RECIBIDO,RECHAZADO',
            'central_observacion' => 'nullable|string',
        ]);

        $item->central_estado = $request->central_estado;
        $item->central_observacion = $request->central_observacion;
        $item->save();

        return response()->json([
            'message' => 'Devolución de central actualizada con éxito.',
            'item' => $item->load('report.agencia', 'report.user', 'product', 'buy.proveedor', 'agencia')
        ]);
    }
}

