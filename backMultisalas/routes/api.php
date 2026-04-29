<?php

use App\Http\Controllers\AnulacionController;
use App\Mail\TestMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});
Route::post('login', [App\Http\Controllers\UserController::class, 'login']);
Route::get('verificarComunicacion', [\App\Http\Controllers\ActivityController::class,'verificarComunicacion']);
Route::get('verificarFacturas', [\App\Http\Controllers\ActivityController::class,'verificarFacturas']);
Route::get('genXML/{id}', [\App\Http\Controllers\SaleController::class,'genXML']);
Route::get('genXMLcandy/{id}', [\App\Http\Controllers\SaleCandyController::class,'genXMLcandy']);
Route::get('genXMLRental/{id}', [\App\Http\Controllers\SaleCandyController::class,'genXMLRental']);

Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::get('anulaciones', [AnulacionController::class, 'index']);
    Route::post('anulaciones', [AnulacionController::class, 'store']);

    Route::post('anulaciones/{anulacion}/autorizar', [AnulacionController::class, 'autorizar']);
    Route::post('anulaciones/{anulacion}/anular',     [AnulacionController::class, 'anular']);
    Route::post('anulaciones/{anulacion}/rechazar',   [AnulacionController::class, 'rechazar']);

    Route::post('getYearMonthFacturas', [\App\Http\Controllers\FacturaController::class,'getYearMonthFacturas']);
    Route::post('import', [\App\Http\Controllers\FacturaController::class,'import']);
    Route::get('buscarFacturas',[App\Http\Controllers\FacturaController::class,'buscarFacturas']);
    Route::post('me', [App\Http\Controllers\UserController::class, 'me']);
    Route::post('logout', [App\Http\Controllers\UserController::class, 'logout']);
    Route::apiResource('user', App\Http\Controllers\UserController::class);
    Route::post('/upload', [\App\Http\Controllers\UploadController::class,'upload']);
    Route::put('/updatePassword/{user}',[\App\Http\Controllers\UserController::class,'updatePassword']);
    Route::put('/updatepermisos/{user}',[\App\Http\Controllers\UserController::class,'updatepermisos']);
    Route::resource('/permiso',\App\Http\Controllers\PermisoController::class);
    Route::get("sendEmail",[\App\Http\Controllers\MailController::class,'sendEmail']);
    Route::resource('movie', \App\Http\Controllers\MovieController::class);
    Route::resource('webMovie', \App\Http\Controllers\WebMovieController::class);
    Route::post('webMovieSearchExternal', [\App\Http\Controllers\WebMovieController::class, 'searchExternal']);
    Route::post('webMovieImportExternal', [\App\Http\Controllers\WebMovieController::class, 'importExternal']);
    Route::get('webMovieProgramaciones', [\App\Http\Controllers\WebMovieController::class, 'listProgramaciones']);
    Route::post('webMovie/{webMovie}/syncProgramaciones', [\App\Http\Controllers\WebMovieController::class, 'syncProgramaciones']);
    Route::resource('cortesia', \App\Http\Controllers\CortesiaController::class);
    Route::get('free', [\App\Http\Controllers\CortesiaController::class,'free']);
    Route::resource('distributor', \App\Http\Controllers\DistributorController::class);
    Route::resource('sala', \App\Http\Controllers\SalaController::class);
    Route::resource('cui', \App\Http\Controllers\CuiController::class);
    Route::resource('price', \App\Http\Controllers\PriceController::class);
    Route::resource('programa', \App\Http\Controllers\ProgramaController::class);
    Route::resource('cufd', \App\Http\Controllers\CufdController::class);
    Route::resource('activity', \App\Http\Controllers\ActivityController::class);
    Route::resource('sale', \App\Http\Controllers\SaleController::class);
    Route::resource('rubro', \App\Http\Controllers\RubroController::class);
    Route::resource('producto', \App\Http\Controllers\ProductoController::class);
    Route::resource('momentaneo', \App\Http\Controllers\MomentaneoController::class);
    Route::resource('document', \App\Http\Controllers\DocumentController::class);
    Route::resource('client', \App\Http\Controllers\ClientController::class);
    Route::resource('eventoSignificativo', \App\Http\Controllers\EventoSignificativoController::class);
    Route::resource('rental', \App\Http\Controllers\RentalController::class);
    Route::resource('prevalorada', \App\Http\Controllers\PrevaloradaController::class);
    Route::resource('vehiculo', \App\Http\Controllers\VehiculoController::class);
    Route::post('rentalConsulta', [\App\Http\Controllers\RentalController::class,'rentalConsulta']);
    Route::get('listProgramacion', [\App\Http\Controllers\ProgramaController::class,'listProgramacion']);
    Route::post('prevaloradaConsulta', [\App\Http\Controllers\PrevaloradaController::class,'prevaloradaConsulta']);
    Route::post('listleyenda', [\App\Http\Controllers\ActivityController::class,'listleyenda']);
    Route::get('motivoanular', [\App\Http\Controllers\ActivityController::class,'motivoanular']);

    Route::post('recepcionPaqueteFactura', [\App\Http\Controllers\EventoSignificativoController::class,'recepcionPaqueteFactura']);
    Route::post('cantidadFacturas', [\App\Http\Controllers\EventoSignificativoController::class,'cantidadFacturas']);
    Route::post('validarPaquete', [\App\Http\Controllers\EventoSignificativoController::class,'validarPaquete']);

    Route::resource('salecandy', \App\Http\Controllers\SaleCandyController::class);
    Route::resource('listaVentaBoleteria', \App\Http\Controllers\ListaVentaBoleteriaController::class);
    Route::resource('listaVentaCandy', \App\Http\Controllers\ListaVentaCandyController::class);
    Route::resource('event', \App\Http\Controllers\EventController::class);

    Route::post('searchClient', [\App\Http\Controllers\ClientController::class,'searchClient']);
    Route::get('datocine', [\App\Http\Controllers\SaleController::class,'datocine']);
    Route::post('totalventa', [\App\Http\Controllers\SaleController::class,'totalventa']);
    Route::post('anularSale', [\App\Http\Controllers\SaleController::class,'anularSale']);


    Route::post('enviarCorreo', [\App\Http\Controllers\SaleController::class,'enviarCorreo']);
    Route::post('anularRental', [\App\Http\Controllers\RentalController::class,'anularRental']);

    Route::post('momentaneoDelete', [\App\Http\Controllers\MomentaneoController::class,'momentaneoDelete']);
    Route::post('momentaneoDeleteUser', [\App\Http\Controllers\MomentaneoController::class,'momentaneoDeleteUser']);
    Route::post('momentaneoDeleteall', [\App\Http\Controllers\MomentaneoController::class,'momentaneoDeleteall']);
    Route::post('movies', [\App\Http\Controllers\SaleController::class, 'movies']);
    Route::post('movietotal', [\App\Http\Controllers\SaleController::class, 'movietotal']);
    Route::post('hours', [\App\Http\Controllers\SaleController::class, 'hours']);
    Route::post('mySeats', [\App\Http\Controllers\SaleController::class, 'mySeats']);
    Route::post('eventSearch', [\App\Http\Controllers\SaleController::class, 'eventSearch']);
    Route::post('disponibleSeats', [\App\Http\Controllers\SaleController::class, 'disponibleSeats']);
    Route::post('upimagenrubro', [\App\Http\Controllers\RubroController::class, 'upimagenrubro']);
    Route::post('upimagenmovie', [\App\Http\Controllers\MovieController::class, 'upimagenmovie']);
    Route::post('upimagenproducto', [\App\Http\Controllers\ProductoController::class, 'upimagenproducto']);
    Route::post('listadoprod', [\App\Http\Controllers\RubroController::class, 'listadoprod']);

    Route::post('cajaBol', [\App\Http\Controllers\SaleController::class, 'cajaBol']);
    Route::post('cajaBolF', [\App\Http\Controllers\SaleController::class, 'cajaBolF']);
    Route::post('cajaBolR', [\App\Http\Controllers\SaleController::class, 'cajaBolR']);
    Route::post('resumenBol', [\App\Http\Controllers\SaleController::class, 'resumenBol']);
    Route::post('resumenBolRF', [\App\Http\Controllers\SaleController::class, 'resumenBolRF']);
    Route::post('reporteCajaBoleteria', [\App\Http\Controllers\SaleController::class, 'reporteCajaBoleteria']);
    Route::post('userbol', [\App\Http\Controllers\SaleController::class, 'userbol']);
    Route::post('usercandy', [\App\Http\Controllers\SaleController::class, 'usercandy']);
    Route::post('resumenCandy', [\App\Http\Controllers\SaleController::class, 'resumenCandy']);
    Route::post('resumenCandyRF', [\App\Http\Controllers\SaleController::class, 'resumenCandyRF']);
    Route::post('reporteCajaCandy', [\App\Http\Controllers\SaleController::class, 'reporteCajaCandy']);
    Route::post('reporteFuncion', [\App\Http\Controllers\SaleController::class, 'reporteFuncion']);
    Route::post('cajaCandy', [\App\Http\Controllers\SaleController::class, 'cajaCandy']);
    Route::post('cajaCandyF', [\App\Http\Controllers\SaleController::class, 'cajaCandyF']);
    Route::post('cajaCandyR', [\App\Http\Controllers\SaleController::class, 'cajaCandyR']);
    Route::get('validarTarjeta/{cod}', [\App\Http\Controllers\SaleController::class, 'validarTarjeta']);
    Route::get('validanit/{nit}', [\App\Http\Controllers\SaleController::class, 'validanit']);

    Route::get('listCufd', [\App\Http\Controllers\CufdController::class,'listCufd']);
    Route::post('reportGenAnulacion', [\App\Http\Controllers\SaleController::class, 'reportGenAnulacion']);
    Route::post('revertirAnularSale', [\App\Http\Controllers\SaleController::class, 'revertirAnularSale']);
    Route::post('generarQr', [\App\Http\Controllers\GenerateQrController::class, 'generarQr']);
    Route::get('statusQr/{qrId}', [\App\Http\Controllers\GenerateQrController::class, 'statusQr']);
    Route::post('cancelarQr/{qrId}', [\App\Http\Controllers\GenerateQrController::class, 'cancelarQr']);
    Route::post('movimientosQr', [\App\Http\Controllers\GenerateQrController::class, 'movimientosQr']);
    Route::get('movimientosQr', [\App\Http\Controllers\GenerateQrController::class, 'movimientosQr']);
    Route::post('ventasParaVincularQr', [\App\Http\Controllers\GenerateQrController::class, 'ventasParaVincularQr']);
    Route::post('vincularVentaQr', [\App\Http\Controllers\GenerateQrController::class, 'vincularVentaQr']);

    Route::post('revertirAnularRental', [\App\Http\Controllers\RentalController::class, 'revertirAnularRental']);
    Route::post('cambioPago', [\App\Http\Controllers\SaleController::class,'cambioPago']);
});
Route::post('anularCuf', [\App\Http\Controllers\SaleController::class,'anularCuf']);
Route::get('anularMasivo', [\App\Http\Controllers\FacturaController::class,'anularMasivo']);
Route::prefix('mobile')->group(function () {
    Route::get('movies', [App\Http\Controllers\MobileController::class, 'movies']);
    Route::get('eventos', [App\Http\Controllers\MobileController::class, 'eventos']);
    Route::get('proximos', [App\Http\Controllers\MobileController::class, 'proximos']);
});
Route::prefix('web')->group(function () {
    Route::get('home', [App\Http\Controllers\WebController::class, 'home']);
    Route::get('movies', [App\Http\Controllers\WebController::class, 'movies']);
    Route::get('movie/{id}', [App\Http\Controllers\WebController::class, 'movie']);
    Route::post('mySeats', [App\Http\Controllers\WebController::class, 'mySeats']);
});
Route::get('test',function (){
    $details=[
        "title"=>"Factura",
        "body"=>"Gracias por su compra",
        "online"=>true,
        "anulado"=>false,
        "cuf"=>"",
        "numeroFactura"=>"",
        "sale_id"=>358702,
        "carpeta"=>"archivos",
    ];
    Mail::to('adimer101@gmail.com')->send(new TestMail($details));
});


Route::get('anulaciones/{anulacion}/pdf', [AnulacionController::class, 'pdf'])->name('anulaciones.pdf');

Route::post('sendPrueba', [\App\Http\Controllers\MailController::class,'sendPrueba']);
