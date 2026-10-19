<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\EquipoQrController;
use App\Http\Controllers\EtiquetaController;

Route::get('/e/{token}', EquipoQrController::class)
    ->middleware(['web', 'throttle:60,1'])
    ->where('token', '[A-Za-z0-9]{32}')
    ->name('equipo.qr');

Route::middleware('web')->group(function () {
    Route::get('/etiquetas/lote', [EtiquetaController::class, 'lote'])->name('etiquetas.lote');
    Route::get('/etiquetas/equipo/{equipo}', [EtiquetaController::class, 'equipo'])->name('etiquetas.equipo');
});

Route::get('/equipos/plantilla-importacion', \App\Http\Controllers\PlantillaImportacionController::class)
    ->middleware('web')
    ->name('equipos.plantilla');

Route::middleware(['web', 'auth'])
    ->get('/ordenes-trabajo/{ot}/informe', \App\Http\Controllers\InformeOtController::class)
    ->whereNumber('ot')
    ->name('ordenes-trabajo.informe');

Route::middleware(['web', 'throttle:30,1'])
    ->get('/v/{token}', \App\Http\Controllers\VerificarInformeController::class)
    ->whereAlphaNumeric('token')
    ->name('informe.verificar');

Route::get('/cotizaciones/{cotizacion}/pdf', \App\Http\Controllers\CotizacionPdfController::class)
    ->middleware('web')
    ->name('cotizaciones.pdf');

Route::get('/facturas/{factura}/pdf', \App\Http\Controllers\FacturaPdfController::class)
    ->middleware('web')
    ->whereNumber('factura')
    ->name('facturas.pdf');

Route::get('/pagos/{pago}/recibo', \App\Http\Controllers\PagoReciboController::class)
    ->middleware('web')
    ->whereNumber('pago')
    ->name('pagos.recibo');

Route::middleware(['web', 'throttle:30,1'])->group(function () {
    Route::get('/cotizacion/{token}', [\App\Http\Controllers\AceptacionCotizacionController::class, 'mostrar'])
        ->whereAlphaNumeric('token')->name('cotizaciones.aceptacion');
    Route::post('/cotizacion/{token}/aceptar', [\App\Http\Controllers\AceptacionCotizacionController::class, 'aceptar'])
        ->whereAlphaNumeric('token')->name('cotizaciones.aceptar');
    Route::post('/cotizacion/{token}/rechazar', [\App\Http\Controllers\AceptacionCotizacionController::class, 'rechazar'])
        ->whereAlphaNumeric('token')->name('cotizaciones.rechazar');
});
