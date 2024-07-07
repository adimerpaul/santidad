<?php

namespace App\Http\Controllers;

use App\Models\Buy;
use App\Http\Requests\StoreBuyRequest;
use App\Http\Requests\UpdateBuyRequest;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BuyController extends Controller{
    //productos por vencer
    public function index(Request $request)
    {
        $search = $request->search;
        $agencia_id = $request->agencia_id;
        $order = $request->order ?? null;

        $buys = Buy::with(['product' => function($query) {
                    $query->select('id', 'nombre','cantidad');
                }, 'user' => function($query) {
                    $query->select('id', 'name');
                }, 'proveedor' => function($query) {
                    $query->select('id', 'nombreRazonSocial');
                }, 'agencia' => function($query) {
                    $query->select('id', 'nombre');
                }]);
        if ($search != null && $search != '') {
            $buys = $buys->whereRaw('(lote = "'.$search.'" or factura = "'.$search.'")')
                ->orWhereHas('product', function($query) use ($search) {
                    $query->where('nombre', 'like', '%'.$search.'%');
                });
            if ($agencia_id != null && $agencia_id != '') {
                $buys = $buys->where('agencia_id', $agencia_id);
            }
        }else{
            if ($order=='Dias para Vencer') {
                $buys = $buys->orderBy('dateExpiry', 'asc')->where('dateExpiry', '>', Carbon::now());
            }elseif ($order=='Fecha de Vencimiento') {
                $buys = $buys->orderBy('dateExpiry', 'asc');
            }elseif ($order=='Fecha de Compra') {
                $buys = $buys->orderBy('date', 'asc');
            }
            if ($agencia_id != null && $agencia_id != '') {
                $buys = $buys->where('agencia_id', $agencia_id);
            }
        }
        return response()->json($buys->paginate(100));
    }
    //productos vencidos
    public function indexVencidos(Request $request)
    {
        $search = $request->search;//         'Dias para Vencer','Fecha de Vencimiento', 'Fecha de Compra'
        $order = $request->order ?? null;

        $buys = Buy::with(['product' => function($query) {
                    $query->select('id', 'nombre','cantidad');
                }, 'user' => function($query) {
                    $query->select('id', 'name');
                }])
            ->with('proveedor','agencia','userBaja')
            ->where('dateExpiry', '<', Carbon::now());
//            ->where('lote', 'like', '%'.$search.'%')
//            ->orWhere('dateExpiry', 'like', '%'.$search.'%')
//            ->orWhere('factura', 'like', '%'.$search.'%');
        if ($search != null && $search != '') {
            $buys = $buys
            ->whereRaw(' (lote like "%'.$search.'%" or dateExpiry like "%'.$search.'%" or factura like "%'.$search.'%")');
        }else{
            if ($order=='Dias para Vencer') {
                $buys = $buys->orderBy('dateExpiry', 'asc')->where('dateExpiry', '<', Carbon::now());
            }elseif ($order=='Fecha de Vencimiento') {
                $buys = $buys->orderBy('dateExpiry', 'asc');
            }elseif ($order=='Fecha de Compra') {
                $buys = $buys->orderBy('date', 'asc');
            }
        }
        return response()->json($buys->paginate(100));
    }

    public function indexVencidosBaja(Request $request)
    {
        $search = $request->search;//         'Dias para Vencer','Fecha de Vencimiento', 'Fecha de Compra'
        $order = $request->order ?? null;
        $fecha_inicio = $request->fecha_inicio;
        $fecha_fin = $request->fecha_fin;

        $buys = Buy::with('proveedor','agencia','userBaja','product','user')
            ->where('user_baja_id','!=',0)
            ->where('dateExpiry', '<=', Carbon::now())
            ->where('fecha_baja', '>=', $fecha_inicio)
            ->where('fecha_baja', '<=', $fecha_fin);

        if ($search != null && $search != '') {
            $buys = $buys->whereRaw(' (lote like "%'.$search.'%" or dateExpiry like "%'.$search.'%" or factura like "%'.$search.'%")');
        }else{
            if ($order=='Dias para Vencer') {
                $buys = $buys->orderBy('dateExpiry', 'asc')->where('dateExpiry', '<', Carbon::now());
            }elseif ($order=='Fecha de Vencimiento') {
                $buys = $buys->orderBy('dateExpiry', 'asc');
            }elseif ($order=='Fecha de Compra') {
                $buys = $buys->orderBy('date', 'asc');
            }
        }
        return response()->json($buys->paginate(100));
    }
    public function darBaja(Request $request)
    {
//        DB::beginTransaction();
//        try {
            $buy = Buy::find($request->id);
            $product = Product::find($buy->product_id);
            $product->cantidad = $product->cantidad - $buy->cantidadBaja;
            if ($request->sucursal_id_baja == 0) {
                $product->cantidadAlmacen = $product->cantidadAlmacen - $buy->cantidadBaja;
            } elseif ($request->sucursal_id_baja == 1) {
                $product->cantidadSucursal1 = $product->cantidadSucursal1 - $buy->cantidadBaja;
            } elseif ($request->sucursal_id_baja == 2) {
                $product->cantidadSucursal2 = $product->cantidadSucursal2 - $buy->cantidadBaja;
            } elseif ($request->sucursal_id_baja == 3) {
                $product->cantidadSucursal3 = $product->cantidadSucursal3 - $buy->cantidadBaja;
            } elseif ($request->sucursal_id_baja == 4) {
                $product->cantidadSucursal4 = $product->cantidadSucursal4 - $buy->cantidadBaja;
            } elseif ($request->sucursal_id_baja == 5) {
                $product->cantidadSucursal5 = $product->cantidadSucursal5 - $buy->cantidadBaja;
            } elseif ($request->sucursal_id_baja == 6) {
                $product->cantidadSucursal6 = $product->cantidadSucursal6 - $buy->cantidadBaja;
            } elseif ($request->sucursal_id_baja == 7) {
                $product->cantidadSucursal7 = $product->cantidadSucursal7 - $buy->cantidadBaja;
            } elseif ($request->sucursal_id_baja == 8) {
                $product->cantidadSucursal8 = $product->cantidadSucursal8 - $buy->cantidadBaja;
            } elseif ($request->sucursal_id_baja == 9) {
                $product->cantidadSucursal9 = $product->cantidadSucursal9 - $buy->cantidadBaja;
            } elseif ($request->sucursal_id_baja == 10) {
                $product->cantidadSucursal10 = $product->cantidadSucursal10 - $buy->cantidadBaja;
            }
            $product->save();

            $buy->user_baja_id = $request->user()->id;
            $buy->cantidadBaja = $request->cantidadBaja;
            $buy->sucursal_id_baja = $request->sucursal_id_baja;
            $buy->description_baja = $request->description_baja;
            $buy->fecha_baja = date("Y-m-d H:i:s");
            $buy->save();
//            DB::commit();
//            return response()->json($buy);
//        } catch (\Exception $e) {
//            DB::rollBack();
//            return response()->json(['message' => 'Error al dar de baja'], 400);
//        }
    }
    public function show(Buy $buy){ return $buy; }
    public function store(StoreBuyRequest $request){
        $buy = new Buy();
        $buy->user_id= $request->user()->id;
        $buy->product_id= $request->product_id;
        $buy->lote= $request->lote;
        $buy->quantity= $request->quantity;
        $buy->price= $request->price;
        $buy->total= $request->quantity * $request->price;
        $buy->dateExpiry= $request->dateExpiry;
        $buy->agencia_id= $request->user()->agencia_id;
        $buy->date= date("Y-m-d");
        $buy->time= date("H:i:s");
        $buy->save();

        $product = Product::find($request->product_id);
        $product->cantidad = $product->cantidad + $buy->quantity;
        $product->cantidadAlmacen = $product->cantidadAlmacen + $buy->quantity;
        $product->save();
        return Buy::with(['product','user'])->findOrFail($buy->id);
    }
    public function compraInsert(Request $request){
        foreach ($request->buys as $buy) {
            if (
                isset($buy['lote']) === false || isset($buy['fechaVencimiento']) === false || isset($buy['cantidadCompra']) === false || isset($buy['price']) === false ||
                $buy['lote'] === '' || $buy['fechaVencimiento'] === '' || $buy['cantidadCompra'] === '' || $buy['price'] === ''
            ) {
                return response()->json(['message' => 'Falta un campo fechaVencimiento, cantidadCompra, price, lote'], 400);
            }
        }
        $insertbuys = [];
        foreach ($request->buys as $buy) {
//            error_log(json_encode($buy));
            $buyNew = new Buy();
            $buyNew->user_id= $request->user()->id;
            $buyNew->product_id= $buy['id'];
            $buyNew->lote= $buy['lote'];
            $buyNew->quantity= $buy['cantidadCompra'];
            $buyNew->price= $buy['price'];
            $buyNew->total= $buy['cantidadCompra'] * $buy['price'];
            $buyNew->dateExpiry= $buy['fechaVencimiento'];
            $buyNew->agencia_id= $request->user()->agencia_id;
            $buyNew->factura= isset($request->factura) ? $request->factura : 0;
            $buyNew->date= date("Y-m-d");
            $buyNew->time= date("H:i:s");
            $buyNew->proveedor_id= $request->proveedor_id;
            $buyNew->save();

            $product = Product::find($buy['id']);
            $product->cantidad = $product->cantidad + $buy['cantidadCompra'];
            if ($request->agencia_id == 0) {
                $product->cantidadAlmacen = $product->cantidadAlmacen + $buy['cantidadCompra'];
            }elseif ($request->agencia_id == 1) {
                $product->cantidadSucursal1 = $product->cantidadSucursal1 + $buy['cantidadCompra'];
            }elseif ($request->agencia_id == 2) {
                $product->cantidadSucursal2 = $product->cantidadSucursal2 + $buy['cantidadCompra'];
            }elseif ($request->agencia_id == 3) {
                $product->cantidadSucursal3 = $product->cantidadSucursal3 + $buy['cantidadCompra'];
            }elseif ($request->agencia_id == 4) {
                $product->cantidadSucursal4 = $product->cantidadSucursal4 + $buy['cantidadCompra'];
            }elseif ($request->agencia_id == 5) {
                $product->cantidadSucursal5 = $product->cantidadSucursal5 + $buy['cantidadCompra'];
            }elseif ($request->agencia_id == 6) {
                $product->cantidadSucursal6 = $product->cantidadSucursal6 + $buy['cantidadCompra'];
            }elseif ($request->agencia_id == 7) {
                $product->cantidadSucursal7 = $product->cantidadSucursal7 + $buy['cantidadCompra'];
            }elseif ($request->agencia_id == 8) {
                $product->cantidadSucursal8 = $product->cantidadSucursal8 + $buy['cantidadCompra'];
            }elseif ($request->agencia_id == 9) {
                $product->cantidadSucursal9 = $product->cantidadSucursal9 + $buy['cantidadCompra'];
            }elseif ($request->agencia_id == 10) {
                $product->cantidadSucursal10 = $product->cantidadSucursal10 + $buy['cantidadCompra'];
            }

            $product->precio = $buy['price'];
            $product->costo = $buy['price']/1.3;
            $product->save();
//            return Buy::with(['product','user'])->findOrFail($buy->id);
        }
        Buy::insert($insertbuys);
    }
    public function update(UpdateBuyRequest $request, Buy $buy){ return $buy->update($request->all()); }
    public function destroy(Buy $buy){ return $buy->delete(); }
}
