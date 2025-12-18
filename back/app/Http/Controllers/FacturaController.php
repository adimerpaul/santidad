<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Buy;
use App\Models\PagoFactura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FacturaController extends Controller
{
    public function index(Request $request)
    {
        $query = Factura::with(['agencia', 'user', 'proveedorRelacion', 'pagos'])
            ->orderBy('fecha_compra', 'desc');
        error_log('Consulta de facturas iniciada');
        error_log($query->toSql());

        // Filtros
        if ($request->filled('agencia_id')) {
            $query->where('agencia_id', $request->agencia_id);
        }

        if ($request->filled('proveedor')) {
            $query->where('proveedor', 'like', '%' . $request->proveedor . '%');
        }

        if ($request->filled('numero_factura')) {
            $query->where('numero_factura', 'like', '%' . $request->numero_factura . '%');
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo_pago')) {
            $query->where('tipo_pago', $request->tipo_pago);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_compra', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_compra', '<=', $request->fecha_hasta);
        }

        return response()->json($query->paginate($request->per_page ?? 50));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'numero_factura' => 'required|unique:facturas',
                'proveedor' => 'required|string|max:255',
                'fecha_compra' => 'required|date',
                'monto_total' => 'required|numeric|min:0',
                'tipo_pago' => 'required|in:Contado,Crédito',
                'agencia_id' => 'required|exists:agencias,id',
                'detalle_compras' => 'nullable|array'
            ]);

            $factura = Factura::create([
                'numero_factura' => $request->numero_factura,
                'proveedor' => $request->proveedor,
                'vendedor' => $request->vendedor,
                'fecha_compra' => $request->fecha_compra,
                'monto_total' => $request->monto_total,
                'tipo_pago' => $request->tipo_pago,
                'metodo_pago' => $request->metodo_pago,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'estado' => $request->tipo_pago === 'Contado' ? 'Pagado' : 'Pendiente',
                'agencia_id' => $request->agencia_id,
                'proveedor_id' => $request->proveedor_id,
                'observaciones' => $request->observaciones,
                'detalle_compras' => $request->detalle_compras,
                'user_id' => auth()->id()
            ]);

            // Si es contado, registrar pago automático
            if ($request->tipo_pago === 'Contado') {
                $factura->registrarPago(
                    $request->monto_total,
                    $request->metodo_pago,
                    $request->referencia_pago,
                    $request->vendedor,
                    'Pago automático por compra al contado'
                );
            }

            // Actualizar factura en las compras relacionadas
            if ($request->filled('detalle_compras')) {
                Buy::whereIn('id', $request->detalle_compras)
                    ->update(['factura_id' => $factura->id]);
            }

            DB::commit();
            return response()->json($factura->load(['agencia', 'user', 'pagos']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear factura: ' . $e->getMessage()], 500);
        }
    }

    public function show(Factura $factura)
    {
        return response()->json($factura->load(['agencia', 'user', 'proveedorRelacion', 'pagos.user', 'buys.product']));
    }

    public function update(Request $request, Factura $factura)
    {
        $request->validate([
            'numero_factura' => 'required|unique:facturas,numero_factura,' . $factura->id,
            'proveedor' => 'required|string|max:255',
            'fecha_compra' => 'required|date',
            'monto_total' => 'required|numeric|min:0',
            'tipo_pago' => 'required|in:Contado,Crédito',
        ]);

        $factura->update($request->all());
        return response()->json($factura->load(['agencia', 'user', 'pagos']));
    }

    public function destroy(Factura $factura)
    {
        // Verificar si tiene pagos registrados
        if ($factura->pagos()->count() > 0) {
            return response()->json(['message' => 'No se puede eliminar una factura con pagos registrados'], 400);
        }

        // Quitar relación con compras
        Buy::where('factura_id', $factura->id)->update(['factura_id' => null]);

        $factura->delete();
        return response()->json(['message' => 'Factura eliminada correctamente']);
    }

    public function registrarPago(Request $request, Factura $factura)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0.01|max:' . $factura->saldo,
            'metodo_pago' => 'nullable|string',
            'referencia' => 'nullable|string',
            'vendedor' => 'nullable|string',
            'observaciones' => 'nullable|string'
        ]);

        $pago = $factura->registrarPago(
            $request->monto,
            $request->metodo_pago,
            $request->referencia,
            $request->vendedor,
            $request->observaciones
        );

        return response()->json([
            'factura' => $factura->fresh()->load(['pagos']),
            'pago' => $pago
        ]);
    }

    // Obtener resumen de facturas
    public function resumen(Request $request)
    {
        $query = Factura::query();

        if ($request->filled('agencia_id')) {
            $query->where('agencia_id', $request->agencia_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_compra', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_compra', '<=', $request->fecha_hasta);
        }

        $totalFacturas = $query->count();
        $montoTotal = $query->sum('monto_total');
        $pagadoTotal = $query->sum('pagado');
        $pendienteTotal = $montoTotal - $pagadoTotal;
        $porcentajePagado = $montoTotal > 0 ? round(($pagadoTotal / $montoTotal) * 100, 2) : 0;

        return response()->json([
            'total_facturas' => $totalFacturas,
            'monto_total' => $montoTotal,
            'pagado_total' => $pagadoTotal,
            'pendiente_total' => $pendienteTotal,
            'porcentaje_pagado' => $porcentajePagado
        ]);
    }
}
