<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\PedidoDetail;
use App\Models\Agencia;
use App\Models\Product;
use App\Models\PedidoModificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    // Historial de pedidos con filtros
    public function historial(Request $request)
    {
        // 1. Cargar relaciones, INCLUYENDO 'proveedor' para que se vea en el historial
        $query = Pedido::with(['agencia', 'user', 'detalles.product', 'proveedor']) 
            ->orderBy('created_at', 'desc');
        
        // Aplicar filtros
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_pedido', '>=', $request->fecha_inicio);
        }
        
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_pedido', '<=', $request->fecha_fin);
        }
        
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        
        if ($request->filled('agencia_id')) {
            $query->where('agencia_id', $request->agencia_id);
        }

        // Filtro por proveedor (Nuevo)
        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
        }
        
        // Seguridad: Filtrar por sucursal si no es admin
        $user = auth()->user();
        if (!$user) {
             return response()->json(['message' => 'No autorizado'], 401);
        }

        if ($user->id != 1) {
            $query->where('agencia_id', $user->agencia_id);
        }
        
        // Paginación
        $pedidos = $query->paginate($request->per_page ?? 10);
        
        // Agregar total de unidades
        $pedidos->getCollection()->transform(function ($pedido) {
            $pedido->total_unidades = $pedido->detalles ? $pedido->detalles->sum('cantidad') : 0;
            return $pedido;
        });
        
        return response()->json([
            'data' => $pedidos->items(),
            'total' => $pedidos->total(),
            'current_page' => $pedidos->currentPage(),
            'per_page' => $pedidos->perPage(),
            'last_page' => $pedidos->lastPage()
        ]);
    }

    // Crear un nuevo pedido
    public function store(Request $request)
    {
        // 1. Validación
        $request->validate([
            'agencia_id' => 'required|integer', 
            
            // --- CORRIGE ESTA LÍNEA ---
            // Antes tenías 'exists:providers,id' o 'exists:proveedores,id'
            // DEBE SER 'exists:clients,id' porque tu tabla se llama 'clients'
            'proveedor_id' => 'required|exists:clients,id', 
            // --------------------------

            'fecha_pedido' => 'required|date',
            'detalles' => 'required|array|min:1', 
            'detalles.*.product_id' => 'required|exists:products,id',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'observacion' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // 2. Crear el Pedido (Cabecera)
            $pedido = Pedido::create([
                'agencia_id' => $request->agencia_id,
                'user_id' => $request->user_id ?? auth()->id(),
                'proveedor_id' => $request->proveedor_id, // <--- AQUÍ GUARDAMOS EL PROVEEDOR
                'fecha_pedido' => $request->fecha_pedido,
                'estado' => 'PENDIENTE',
                'observacion' => $request->observacion
            ]);

            // 3. Crear los Detalles
            foreach ($request->detalles as $detalle) {
                PedidoDetail::create([
                    'pedido_id' => $pedido->id,
                    'product_id' => $detalle['product_id'],
                    'cantidad' => $detalle['cantidad']
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Pedido registrado con éxito',
                'pedido' => $pedido
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al guardar el pedido', 
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // Detalles de un pedido específico
    public function detalles($id)
    {
        // Incluimos 'proveedor' aquí también por si acaso
        $pedido = Pedido::with(['detalles.product', 'proveedor'])->findOrFail($id);
        
        // Recorremos detalles para buscar acciones previas (si las hubo)
        foreach ($pedido->detalles as $detalle) {
            $modificacionDetalle = DB::table('pedido_modificacion_detalles')
                ->where('pedido_detail_id', $detalle->id)
                ->orderBy('id', 'desc')
                ->first();

            if ($modificacionDetalle) {
                $detalle->accion_aplicada = $modificacionDetalle->accion;
                $detalle->cantidad_aprobada = $modificacionDetalle->cantidad_nueva;
            } else {
                $detalle->accion_aplicada = null;
                $detalle->cantidad_aprobada = $detalle->cantidad;
            }
        }
        
        return response()->json([
            'detalles' => $pedido->detalles
        ]);
    }
    
    // Stock del producto por sucursal
    public function stockSucursales($productId)
    {
        try {
            $product = Product::findOrFail($productId);
            
            try {
                 $stockSucursales = DB::table('inventario')
                    ->where('product_id', $productId)
                    ->join('agencias', 'inventario.agencia_id', '=', 'agencias.id')
                    ->select('agencias.id as agencia_id', 'agencias.nombre as agencia_nombre', 'inventario.cantidad as stock')
                    ->get();
                
                if ($stockSucursales->count() > 0) {
                    return response()->json($stockSucursales);
                }
            } catch (\Exception $e) {}

            $stockFallback = [
                [
                    'agencia_id' => 0, 
                    'agencia_nombre' => 'Stock General', 
                    'stock' => $product->cantidad
                ]
            ];
            
            return response()->json($stockFallback);

        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    // Sugerencias de cantidades
    public function sugerenciasPedido($productId, Request $request)
    {
        $cantidadSolicitada = $request->cantidad_solicitada;
        
        $sugerencias = [
            ['tipo' => 'MINIMO', 'cantidad' => max(1, floor($cantidadSolicitada * 0.5))],
            ['tipo' => 'MAXIMO', 'cantidad' => ceil($cantidadSolicitada * 1.5)],
            ['tipo' => 'PROMEDIO', 'cantidad' => round($cantidadSolicitada)],
            ['tipo' => 'SUGERIDO', 'cantidad' => $cantidadSolicitada]
        ];
        
        return response()->json($sugerencias);
    }
    
    // Historial de modificaciones
    public function modificaciones($pedidoId)
    {
        $modificaciones = PedidoModificacion::with([
                'user', 
                'detalles.pedidoDetail.product'
            ])
            ->where('pedido_id', $pedidoId)
            ->orderBy('created_at', 'desc')
            ->get();
    
        return response()->json($modificaciones->map(function($mod) {
            return [
                'id' => $mod->id,
                'accion' => $mod->accion,
                'fecha' => $mod->created_at,
                'usuario_nombre' => $mod->user->name ?? 'Sistema',
                'observacion' => $mod->observacion,
                'detalles' => $mod->detalles->map(function($det) {
                    return [
                        'id' => $det->id,
                        'pedido_detail_id' => $det->pedido_detail_id,
                        'product_id' => $det->pedidoDetail->product_id ?? null,
                        'producto_nombre' => $det->pedidoDetail->product->nombre ?? 'Producto desconocido',
                        'cantidad_anterior' => $det->cantidad_anterior,
                        'cantidad_nueva' => $det->cantidad_nueva,
                    ];
                })
            ];
        }));
    }
    
    // Aplicar acción a un pedido
    public function accion(Request $request)
    {
        $request->validate([
            'pedido_id' => 'required|exists:pedidos,id',
            'accion' => 'required|string',
            'observacion' => 'nullable|string',
            'modificaciones' => 'nullable|array'
        ]);

        try {
            DB::beginTransaction();

            $pedido = Pedido::findOrFail($request->pedido_id);
            $estadoAnterior = $pedido->estado;
            
            // 1. Actualizar estado
            $pedido->estado = $request->accion;
            $pedido->save();
            
            // 2. Registrar modificación
            $modificacion = PedidoModificacion::create([
                'pedido_id' => $pedido->id,
                'user_id' => auth()->id(),
                'accion' => $request->accion,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $request->accion,
                'observacion' => $request->observacion
            ]);
            
            // 3. Modificaciones en productos
            if ($request->filled('modificaciones')) {
                foreach ($request->modificaciones as $modProducto) {
                    
                    $accionInput = $modProducto['accion'] ?? null;
                    $accionProducto = 'SIN_CAMBIOS';

                    if (is_array($accionInput) && isset($accionInput['value'])) {
                        $accionProducto = $accionInput['value']; 
                    } elseif (is_string($accionInput)) {
                        $accionProducto = $accionInput;
                    }

                    $cantidadNueva = isset($modProducto['cantidad_aprobada']) ? $modProducto['cantidad_aprobada'] : 0;
                    
                    $detalle = PedidoDetail::find($modProducto['pedido_detail_id']);
                    
                    if ($detalle) {
                        DB::table('pedido_modificacion_detalles')->insert([
                            'modificacion_id' => $modificacion->id,
                            'pedido_detail_id' => $detalle->id,
                            'cantidad_anterior' => $detalle->cantidad,
                            'cantidad_nueva' => $cantidadNueva, 
                            'accion' => $accionProducto,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        
                        // Actualizar cantidad original si cambió
                        if ($detalle->cantidad != $cantidadNueva) {
                            $detalle->cantidad = $cantidadNueva;
                            $detalle->save();
                        }
                    }
                }
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Acción aplicada correctamente',
                'pedido' => $pedido
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Error al procesar la acción',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }
}