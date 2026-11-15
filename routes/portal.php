<?php

use App\Http\Controllers\PortalDocumentoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/portal-docs/cotizacion/{id}', [PortalDocumentoController::class, 'cotizacion'])->whereNumber('id')->name('portal.doc.cotizacion');
    Route::get('/portal-docs/factura/{id}', [PortalDocumentoController::class, 'factura'])->whereNumber('id')->name('portal.doc.factura');
    Route::get('/portal-docs/recibo/{id}', [PortalDocumentoController::class, 'recibo'])->whereNumber('id')->name('portal.doc.recibo');
    Route::get('/portal-docs/contrato/{id}', [PortalDocumentoController::class, 'contrato'])->whereNumber('id')->name('portal.doc.contrato');
});
