<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});
Route::post('/login',[\App\Http\Controllers\UserController::class,'login']);
Route::post('upload/{id}/{option}', [\App\Http\Controllers\UploadController::class, 'upload']);
Route::resource('/carousels',\App\Http\Controllers\CarouselController::class);
Route::get('/productos',[\App\Http\Controllers\TiendaController::class,'productos']);
Route::group(['middleware'=>'auth:sanctum'],function (){
    Route::post('/me', [\App\Http\Controllers\UserController::class,'me']);
    Route::post('/logout', [\App\Http\Controllers\UserController::class,'logout']);
    Route::resource('/user',\App\Http\Controllers\UserController::class);
    Route::resource('/categories',\App\Http\Controllers\CategoryController::class);
    Route::resource('/subcategories',\App\Http\Controllers\SubcategoryController::class);
    Route::resource('/agencias',\App\Http\Controllers\AgenciaController::class);
    Route::resource('/products',\App\Http\Controllers\ProductController::class);
    Route::resource('/unids',\App\Http\Controllers\UnidController::class);
//    unidAll
    Route::get('/unidAll',[\App\Http\Controllers\UnidController::class,'unidAll']);
    Route::get('/productsAll',[\App\Http\Controllers\ProductController::class,'productsAll']);
//    duplicateProduct
    Route::post('/duplicateProduct',[\App\Http\Controllers\ProductController::class,'duplicateProduct']);
    Route::post('/moverProducto',[\App\Http\Controllers\ProductController::class,'moverProducto']);
    Route::get('/productsSale',[\App\Http\Controllers\ProductController::class,'productsSale']);
    Route::post('/agregarSucursal',[\App\Http\Controllers\ProductController::class,'agregarSucursal']);
    Route::resource('/clients',\App\Http\Controllers\ClientController::class);
    Route::get('/clientsProvider',[\App\Http\Controllers\ClientController::class,'indexProvider']);
    Route::get('/reportTotal/{fechaInicio}/{fechaFin}',[\App\Http\Controllers\SalesController::class,'reportTotal']);
    Route::get('/reportTotalIngreso/{fechaInicio}/{fechaFin}',[\App\Http\Controllers\SalesController::class,'reportTotalIngreso']);
    Route::get('/reportTotalEgreso/{fechaInicio}/{fechaFin}',[\App\Http\Controllers\SalesController::class,'reportTotalEgreso']);
    Route::resource('/documents',\App\Http\Controllers\DocumentController::class);
    Route::get('/providers',[\App\Http\Controllers\ClientController::class,'providers']);
    Route::put('/updatePassword/{user}',[\App\Http\Controllers\UserController::class,'updatePassword']);
    Route::resource('/sales',\App\Http\Controllers\SalesController::class);
    Route::get('/salesAnular/{id}',[\App\Http\Controllers\SalesController::class,'salesAnular']);
    Route::resource('/buys',\App\Http\Controllers\BuyController::class);
    Route::get('/indexVencidos',[\App\Http\Controllers\BuyController::class,'indexVencidos']);
    Route::get('/indexVencidosBaja',[\App\Http\Controllers\BuyController::class,'indexVencidosBaja']);
    Route::post('/darBaja',[\App\Http\Controllers\BuyController::class,'darBaja']);
    Route::post('/compraInsert',[\App\Http\Controllers\BuyController::class,'compraInsert']);
    Route::post('/salesGasto',[\App\Http\Controllers\SalesController::class,'salesGasto']);
    Route::post('/searchClient', [\App\Http\Controllers\ClientController::class,'searchClient']);
    Route::get('/betweenDates/{fechaInicio}/{fechaFin}',[\App\Http\Controllers\SalesController::class,'betweenDates']);
    Route::get('/env',[\App\Http\Controllers\SalesController::class,'env']);



    Route::get('/historySucursal',[\App\Http\Controllers\TransferHistoryController::class,'historySucursal']);
    Route::get('/historySucursalProduct',[\App\Http\Controllers\TransferHistoryController::class,'historySucursalProduct']);
});
