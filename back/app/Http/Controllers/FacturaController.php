<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Buy;
use App\Models\PagoFactura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FacturaController extends Controller
{
    /**
     * Listar facturas con filtros y seguridad por ID de usuario.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Factura::with(['agencia', 'user', 'proveedorRelacion', 'pagos'])
            ->orderBy('fecha_compra', 'desc');

        // =================================================================
        // 🔒 SEGURIDAD: LÓGICA DE VISUALIZACIÓN
        // =================================================================
        if ($user->id != 1) { // Si NO es el Super Admin
            if ($user->agencia_id == 1) {
                // EXCEPCIÓN: La Agencia 1 puede ver lo suyo Y el Almacén (null o 0)
                $query->where(function($q) use ($user) {
                    $q->where('agencia_id', $user->agencia_id)
                      ->orWhereNull('agencia_id')
                      ->orWhere('agencia_id', 0);
                });
            } else {
                // EL RESTO: Solo ve estrictamente su agencia
                $query->where('agencia_id', $user->agencia_id);
            }
        }
        // =================================================================

        // Filtros del Frontend
        if ($request->filled('agencia_id')) {
            // Validamos que el usuario tenga permiso de usar este filtro
            if ($request->agencia_id == 0) {
                // Solo Admin o Agencia 1 pueden filtrar por Almacén
                if ($user->id == 1 || $user->agencia_id == 1) {
                    $query->whereNull('agencia_id');
                }
            } else {
                $query->where('agencia_id', $request->agencia_id);
            }
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

    /**
     * Crear nueva factura.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'fecha_compra' => 'required|date',
                'monto_total' => 'required|numeric|min:0',
                'tipo_pago' => 'required|in:Contado,Crédito',
                'detalle_compras' => 'nullable|array'
            ]);

            $user = auth()->user();

            // 🔒 SEGURIDAD AL CREAR:
            // Si NO es el Admin (ID 1), la factura se asigna OBLIGATORIAMENTE a su agencia.
            if ($user->id != 1) {
                $request->merge(['agencia_id' => $user->agencia_id]);
            } else {
                // Si ES Admin, respetamos lo que envíe, pero convertimos 0 a null
                if ($request->agencia_id == 0) {
                    $request->merge(['agencia_id' => null]);
                }
            }

            if ($request->proveedor_id == 0) {
                $request->merge(['proveedor_id' => null]);
            }

            $factura = Factura::create([
                'numero_factura' => $request->numero_factura,
                'proveedor' => $request->proveedor,
                'vendedor' => $request->vendedor, // Nombre del vendedor (texto)
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
                'user_id' => $user->id
            ]);

            // Registrar pago automático si es al contado
            if ($request->tipo_pago === 'Contado') {
                $factura->registrarPago(
                    $request->monto_total,
                    $request->metodo_pago,
                    $request->referencia_pago,
                    $request->vendedor,
                    'Pago automático por compra al contado'
                );
            }

            // =================================================================
            // ✅ CORRECCIÓN CLAVE AQUÍ PARA GUARDAR EL VENDEDOR EN LAS COMPRAS
            // =================================================================
            if ($request->filled('detalle_compras')) {
                // Preparamos los datos a actualizar en la tabla 'buys'
                $datosUpdate = ['factura_id' => $factura->id];

                // Si viene un vendedor_id, lo agregamos a la actualización
                if ($request->filled('vendedor_id')) {
                    $datosUpdate['vendedor_id'] = $request->vendedor_id;
                }

                // Actualizamos todas las compras seleccionadas
                Buy::whereIn('id', $request->detalle_compras)
                    ->update($datosUpdate);
            }
            // =================================================================

            DB::commit();
            // Cargamos 'buys.vendedor' para que el frontend lo reciba de inmediato si es necesario
            return response()->json($factura->load(['agencia', 'user', 'pagos', 'buys.vendedor']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear factura: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Ver detalle de una factura.
     */
    public function show(Factura $factura)
    {
        $user = auth()->user();

        // 🔒 SEGURIDAD: Solo Admin (ID 1) o el dueño de la agencia pueden ver
        if ($user->id != 1 && $factura->agencia_id != $user->agencia_id) {
             return response()->json(['message' => 'No tiene permiso para ver esta factura'], 403);
        }

        // Aquí ya tenías 'buys.vendedor', esto está correcto.
        return response()->json($factura->load([
            'agencia', 
            'user', 
            'proveedorRelacion', 
            'pagos.user', 
            'buys.product', 
            'buys.vendedor' // Esto permite ver el vendedor en el detalle
        ]));
    }

    /**
     * Actualizar factura.
     */
    public function update(Request $request, Factura $factura)
    {
        $user = auth()->user();

        if ($user->id != 1) {
             return response()->json(['message' => 'Solo el administrador puede editar facturas'], 403);
        }

        $request->validate([
            'numero_factura' => 'required|unique:facturas,numero_factura,' . $factura->id,
            'proveedor' => 'required|string|max:255',
            'fecha_compra' => 'required|date',
            'monto_total' => 'required|numeric|min:0',
            'tipo_pago' => 'required|in:Contado,Crédito',
        ]);

        $factura->update($request->all());
        
        // (Opcional) Si al editar también quieres actualizar el vendedor en las compras,
        // deberías agregar lógica similar al store aquí si fuera necesario.

        return response()->json($factura->load(['agencia', 'user', 'pagos']));
    }

    /**
     * Eliminar factura.
     */
    public function destroy(Factura $factura)
    {
        $user = auth()->user();

        if ($user->id != 1) {
             return response()->json(['message' => 'Solo el administrador puede eliminar facturas'], 403);
        }

        if ($factura->pagos()->count() > 0) {
            return response()->json(['message' => 'No se puede eliminar una factura con pagos registrados'], 400);
        }

        // Al eliminar, quitamos el ID de factura de las compras, pero mantenemos el producto
        Buy::where('factura_id', $factura->id)->update(['factura_id' => null]);

        $factura->delete();
        return response()->json(['message' => 'Factura eliminada correctamente']);
    }

    /**
     * Registrar un pago a la factura.
     */
    public function registrarPago(Request $request, Factura $factura)
    {
        $user = auth()->user();

        if ($user->id != 1 && $factura->agencia_id != $user->agencia_id) {
             return response()->json(['message' => 'No autorizado para registrar pagos en esta factura'], 403);
        }

        $request->validate([
            'monto' => 'required|numeric|min:0.01|max:' . ($factura->saldo + 0.5),
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

    /**
     * Obtener resumen de montos (totales, deuda, etc).
     */
    public function resumen(Request $request)
    {
        $user = $request->user();
        $base = Factura::query();

        // 1. SEGURIDAD
        if ($user->id != 1) {
            if ($user->agencia_id == 1) {
                $base->where(function($q) use ($user) {
                    $q->where('agencia_id', $user->agencia_id)->orWhereNull('agencia_id')->orWhere('agencia_id', 0);
                });
            } else {
                $base->where('agencia_id', $user->agencia_id);
            }
        }

        // 2. FILTROS
        if ($request->filled('agencia_id')) {
            if ($request->agencia_id == 0) $base->whereNull('agencia_id');
            else $base->where('agencia_id', $request->agencia_id);
        }
        if ($request->filled('fecha_desde')) $base->whereDate('fecha_compra', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta')) $base->whereDate('fecha_compra', '<=', $request->fecha_hasta);

        // 3. CÁLCULOS MATEMÁTICOS BLINDADOS
        $totalFacturas = (clone $base)->count();
        $montoTotal  = round((float) (clone $base)->sum('monto_total'), 2);
        $pagadoTotal = round((float) (clone $base)->sum('pagado'), 2);

        $diff = round($montoTotal - $pagadoTotal, 2);
        $tolerancia = 0.50; 

        // A. Corrección de DEUDA
        if ($diff > 0 && $diff <= $tolerancia) {
            $diff = 0; 
        }
        $pendienteTotal = max(0, $diff);

        // B. Corrección de SOBREPAGO
        $sobrepagoRaw = $pagadoTotal - $montoTotal;
        $sobrepagoTotal = 0;
        if ($sobrepagoRaw > 0) {
            if ($sobrepagoRaw <= $tolerancia) {
                $sobrepagoTotal = 0;
            } else {
                $sobrepagoTotal = round($sobrepagoRaw, 2);
            }
        }

        // C. CÁLCULO DEL PORCENTAJE
        if ($montoTotal > 0) {
            if ($pendienteTotal == 0) {
                $porcentajePagado = 100;
            } else {
                $porcentajePagado = round(($pagadoTotal / $montoTotal) * 100, 2);
                $porcentajePagado = min(100, $porcentajePagado);
            }
        } else {
            $porcentajePagado = 0;
        }

        return response()->json([
            'total_facturas'     => $totalFacturas,
            'monto_total'        => $montoTotal,
            'pagado_total'       => $pagadoTotal,
            'pendiente_total'    => $pendienteTotal,
            'porcentaje_pagado'  => $porcentajePagado,
            'sobrepago_total'    => $sobrepagoTotal
        ]);
    }
}