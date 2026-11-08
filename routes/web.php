<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\EquipoQrController;
use App\Http\Controllers\EtiquetaController;

Route::get('/e/{token}', EquipoQrController::class)
    ->middleware(['web', 'throttle:60,1'])
    ->where('token', '[A-Za-z0-9]{32}')
    ->name('equipo.qr');

Route::get('/ingresar', function () {
    $opciones = collect([
        'tecnico' => 'Soy técnico',
        'portal' => 'Soy cliente',
    ])->map(fn ($nombre, $id) => ['nombre' => $nombre, 'url' => \Filament\Facades\Filament::getPanel($id)->getLoginUrl()])
        ->filter(fn ($o) => filled($o['url']))->values();

    return view('equipo.ingreso', ['opciones' => $opciones]);
})->middleware(['web', 'throttle:60,1'])->name('equipo.ingreso');

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

Route::middleware('auth')
    ->get('/ordenes-trabajo/{ot}/entregable/{tipo}', \App\Http\Controllers\EntregableOtController::class)
    ->whereIn('tipo', ['garantia', 'capacitacion', 'calendario', 'protocolo'])
    ->name('ordenes-trabajo.entregable');

Route::middleware('auth')
    ->get('/equipos/documentos/{media}', \App\Http\Controllers\DocumentoEquipoController::class)
    ->whereNumber('media')
    ->name('equipos.documento');

Route::middleware('auth')
    ->get('/ordenes-trabajo/documentos/{media}', \App\Http\Controllers\DocumentoOtController::class)
    ->whereNumber('media')
    ->name('ordenes-trabajo.documento');

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
    Route::get('/contrato/firma/{token}', [\App\Http\Controllers\FirmaContratoController::class, 'mostrar'])
        ->whereAlphaNumeric('token')->name('contratos.firma');
    Route::post('/contrato/firma/{token}', [\App\Http\Controllers\FirmaContratoController::class, 'firmar'])
        ->whereAlphaNumeric('token')->name('contratos.firma.firmar');
});

Route::middleware(['web', 'auth', 'throttle:120,1'])->prefix('api-tecnico')->group(function () {
    Route::post('/paquete', [\App\Http\Controllers\OfflineTecnicoController::class, 'paquete'])->name('offline.paquete');
    Route::post('/ficha', [\App\Http\Controllers\OfflineTecnicoController::class, 'ficha'])->name('offline.ficha');
});

Route::get('/sitemap.xml', \App\Http\Controllers\SitemapController::class);
Route::view('/', 'web.index')->name('home');


Route::post('/contacto', [\App\Http\Controllers\ContactoController::class, 'store'])
    ->middleware(['web', 'throttle:5,1'])
    ->name('contacto.enviar');

Route::get('/documentos/previa/{tipo}', \App\Http\Controllers\DocumentoPreviaController::class)
    ->middleware(['web', 'auth'])
    ->name('documentos.previa');

Route::get('/documentos/{documento}/pdf', \App\Http\Controllers\DocumentoCatalogoPdfController::class)
    ->middleware(['web', 'auth'])
    ->whereNumber('documento')
    ->name('documentos.pdf');

// Páginas del sitio público (administradas desde el panel). Debe ir al final.
Route::get('/{slug}', [\App\Http\Controllers\PaginaWebController::class, 'show'])
    ->middleware('web')
    ->where('slug', \App\Models\WebPagina::patronRuta())
    ->name('web.pagina');

Route::middleware(['web', 'auth', 'throttle:30,1'])
    ->post('/e/{token}/estado', [\App\Http\Controllers\EquipoQrController::class, 'cambiarEstado'])
    ->name('equipo.estado');

Route::middleware(['web', 'auth', 'throttle:120,1'])
    ->get('/e/{token}/foto/{media}', [\App\Http\Controllers\EquipoQrController::class, 'foto'])
    ->whereNumber('media')
    ->name('equipo.foto');

Route::get('/liquidaciones/{liquidacion}/comprobante', \App\Http\Controllers\ComprobanteLiquidacionController::class)
    ->middleware('web')
    ->whereNumber('liquidacion')
    ->name('liquidaciones.comprobante');

Route::get('/documentos-terceros/{documento}/archivo', \App\Http\Controllers\DocumentoTerceroArchivoController::class)->name('documentos-terceros.archivo');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/push/clave', [\App\Http\Controllers\PushController::class, 'clave']);
    Route::post('/push/suscribir', [\App\Http\Controllers\PushController::class, 'guardar']);
    Route::post('/push/baja', [\App\Http\Controllers\PushController::class, 'borrar']);
});