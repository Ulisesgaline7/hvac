<?php

use App\Models\Cotizacion;
use App\Models\Factura;
use App\Services\CotizacionFlujo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function cotizacionEnviadaConVisita(array $visita = []): Cotizacion
{
    $ot = otListaParaCerrar();
    $ot->update($visita);

    return Cotizacion::create([
        'cliente_id' => $ot->cliente_id, 'estado' => 'enviada', 'visita_ot_id' => $ot->id,
    ]);
}

it('al rechazar se factura la visita con ISV', function () {
    $c = cotizacionEnviadaConVisita();

    app(CotizacionFlujo::class)->rechazar($c, 'muy cara');

    $f = Factura::where('cotizacion_id', $c->id)->first();
    expect($f)->not->toBeNull()
        ->and((float) $f->subtotal)->toBe((float) config('visita.costo'))
        ->and((float) $f->total)->toBe(round(config('visita.costo') * (1 + config('tarifario.isv')), 2));
});

it('usa el monto propio de la visita si lo tiene', function () {
    $c = cotizacionEnviadaConVisita(['visita_monto' => 800]);

    app(CotizacionFlujo::class)->rechazar($c);

    expect((float) Factura::where('cotizacion_id', $c->id)->first()->subtotal)->toBe(800.0);
});

it('una visita marcada sin costo no se factura', function () {
    $c = cotizacionEnviadaConVisita(['visita_cobro' => 'gratis']);

    app(CotizacionFlujo::class)->rechazar($c);

    expect(Factura::count())->toBe(0);
});

it('rechazar una cotización sin visita no factura nada', function () {
    $ot = otListaParaCerrar();
    $c = Cotizacion::create(['cliente_id' => $ot->cliente_id, 'estado' => 'enviada']);

    app(CotizacionFlujo::class)->rechazar($c);

    expect(Factura::count())->toBe(0);
});
