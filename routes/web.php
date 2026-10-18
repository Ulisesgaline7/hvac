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
