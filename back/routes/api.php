<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\CarouselController;
use App\Http\Controllers\TiendaController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\AgenciaController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\UnidController;
use App\Http\Controllers\BuyController;
use App\Http\Controllers\TransferHistoryController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PedidoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- Notificaciones (públicas si así lo quieres) ---
Route::get('/notificaciones/{agencia}', [NotificacionController::class, 'index']);
Route::put('/notificaciones/{id}/leer', [NotificacionController::class, 'marcarComoLeida']);

Route::get('distribuidoras-list', [ProductController::class, 'getDistribuidoras']);


// --- Público / pre-auth ---
Route::post('/login', [UserController::class,'login']);
Route::post('upload/{id}/{option}', [UploadController::class, 'upload']);
Route::get('/carouselsPage', [CarouselController::class,'carouselsPage']);
Route::get('/carouselsMini', [CarouselController::class,'carouselsMini']);
Route::get('/productos', [TiendaController::class,'productos']);
Route::get('/sucursales', [TiendaController::class,'sucursales']);
Route::get('/productos/{id}', [TiendaController::class,'productosId']);
Route::get('/top-sellers', [SalesController::class, 'topSellers']);
Route::get('carouselsMedio', [CarouselController::class, 'carouselsMedio']);

Route::post('/orders', [OrderController::class, 'store']);                  // crear pedido
Route::get('/orders/{orderNumber}', [OrderController::class, 'showByNumber']); // recuperar por número (para “ventas” luego)
Route::post('/stock/verificar-sucursal', [ProductController::class, 'verificarStockSucursal']);

// ✅ Sugerencias predictivas (antes de /products)
Route::get('/products/suggest', [ProductController::class, 'suggest'])
    ->name('products.suggest')
    ->middleware('throttle:60,1');

// === PÚBLICO (solo lectura) ===
Route::get('/categories', [CategoryController::class, 'index'])
    ->middleware('throttle:120,1');
Route::get('/categories/{category}', [CategoryController::class, 'show'])
    ->middleware('throttle:120,1');

Route::get('/subcategories', [SubcategoryController::class, 'index'])
    ->middleware('throttle:120,1');
Route::get('/subcategories/{subcategory}', [SubcategoryController::class, 'show'])
    ->middleware('throttle:120,1');

Route::get('/productsSale', [ProductController::class,'productsSale'])
    ->middleware('throttle:120,1');

// --- PROTEGIDAS ---
Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::post('/me', [UserController::class,'me']);
    Route::post('/logout', [UserController::class,'logout']);

    Route::resource('/user', UserController::class);

    // Categories: escritura protegida (index/show ya públicos)
    Route::resource('/categories', CategoryController::class)->except(['index','show']);

    // Subcategories: escritura protegida (index/show ya públicos)
    Route::resource('/subcategories', SubcategoryController::class)->except(['index','show']);

    Route::resource('/agencias', AgenciaController::class);
    Route::resource('/products', ProductController::class);
    Route::resource('/unids', UnidController::class);

    Route::get('/carousels', [CarouselController::class,'index']);
    Route::post('/carousels', [CarouselController::class,'store']);
    Route::delete('/carousels/{carousel}', [CarouselController::class,'destroy']);
    Route::put('/carousels/{id}', [CarouselController::class,'update']);
    Route::post('/carouselsFile/{id}', [CarouselController::class,'storeFile']);

    Route::get('/unidAll', [UnidController::class,'unidAll']);
    Route::get('/productsAll', [ProductController::class,'productsAll']);

    Route::post('/duplicateProduct', [ProductController::class,'duplicateProduct']);
    Route::post('/moverProducto', [ProductController::class,'moverProducto']);
    Route::post('/transferencias-multiples', [ProductController::class, 'transferenciasMultiples']);
    // OJO: /productsSale YA está público arriba, así que aquí LO QUITAMOS para no duplicar
    Route::post('/agregarSucursal', [ProductController::class,'agregarSucursal']);

    Route::resource('/clients', ClientController::class);
    Route::get('/clientsProvider', [ClientController::class,'indexProvider']);
    Route::get('/reportTotal/{fechaInicio}/{fechaFin}', [SalesController::class,'reportTotal']);
    Route::get('/reportTotalIngreso/{fechaInicio}/{fechaFin}', [SalesController::class,'reportTotalIngreso']);
    Route::get('/reportTotalEgreso/{fechaInicio}/{fechaFin}', [SalesController::class,'reportTotalEgreso']);
    Route::resource('/documents', DocumentController::class);
    Route::get('/providers', [ClientController::class,'providers']);
    Route::put('/updatePassword/{user}', [UserController::class,'updatePassword']);

    Route::resource('/sales', SalesController::class);
    Route::get('/salesAnular/{id}', [SalesController::class,'salesAnular']);

    Route::resource('/buys', BuyController::class);
    Route::get('/indexVencidos', [BuyController::class,'indexVencidos']);
    Route::get('/indexVencidosBaja', [BuyController::class,'indexVencidosBaja']);
    Route::post('/darBaja', [BuyController::class,'darBaja']);
    Route::post('/compraInsert', [BuyController::class,'compraInsert']);

    Route::post('/salesGasto', [SalesController::class,'salesGasto']);
    Route::post('/searchClient', [ClientController::class,'searchClient']);
    Route::get('/betweenDates/{fechaInicio}/{fechaFin}', [SalesController::class,'betweenDates']);
    Route::get('/env', [SalesController::class,'env']);

    Route::get('/historySucursal', [TransferHistoryController::class,'historySucursal']);
    Route::get('/historySucursalProduct', [TransferHistoryController::class,'historySucursalProduct']);

    Route::get('productos/{id}/stock', [ProductController::class, 'verificarStock']);
    Route::post('/verificar-stock-venta', [ProductController::class, 'verificarStockVenta']);

    Route::apiResource('facturas', \App\Http\Controllers\FacturaController::class);
    Route::post('facturas/{factura}/pagar', [\App\Http\Controllers\FacturaController::class, 'registrarPago']);
    Route::get('facturas-resumen', [\App\Http\Controllers\FacturaController::class, 'resumen']);

        // 1. PRIMERO: Las rutas específicas (custom)
        Route::get('pedidos/historial', [PedidoController::class, 'historial']);
        Route::post('pedidos/accion', [PedidoController::class, 'accion']);
        Route::get('pedidos/{id}/detalles', [PedidoController::class, 'detalles']);
        Route::get('pedidos/{id}/modificaciones', [PedidoController::class, 'modificaciones']);

        // 2. SEGUNDO: Las rutas de recursos (generales)
        // Esto crea: index, store, show, update, destroy
        Route::apiResource('pedidos', PedidoController::class);

        // Rutas de productos (auxiliares)
        Route::get('products/{id}/stock-sucursales', [PedidoController::class, 'stockSucursales']);
        Route::get('products/{id}/sugerencias-pedido', [PedidoController::class, 'sugerenciasPedido']);
        
});