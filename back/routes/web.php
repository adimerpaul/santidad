<?php

use App\Http\Controllers\TiendaWebController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Tienda pública (farmaciasantidaddivina.com)
|--------------------------------------------------------------------------
| Páginas renderizadas con Blade para SEO. La API sigue en routes/api.php.
*/

Route::controller(TiendaWebController::class)->group(function () {
    Route::get('/', 'home')->name('tienda.home');
    Route::get('/buscar', 'buscar')->name('tienda.buscar');
    Route::get('/descuentos', 'descuentos')->name('tienda.descuentos');
    Route::get('/sucursales', 'sucursales')->name('tienda.sucursales');
    Route::get('/categoria/{id}/{slug?}', 'categoria')->whereNumber('id')->name('tienda.categoria');
    Route::get('/producto/{id}/{slug?}', 'producto')->whereNumber('id')->name('tienda.producto');
    Route::get('/sitemap.xml', 'sitemap')->name('tienda.sitemap');

    // Enlaces de la tienda anterior (frontTienda)
    Route::get('/detalle-producto/{id}/{nombre?}', 'legacyProducto')->whereNumber('id');
});
