<?php

use App\Models\Contrato;
use App\Models\Factura;
use App\Services\FacturacionContratos;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function contratoConCuota(array $extra = []): Contrato
{
    $ot = otListaParaCerrar();

    return Contrato::create($extra + [
        'cliente_id' => $ot->cliente_id, 'tipo' => 'preventivo_recurrente', 'estado' => 'activo',
        'vigencia_inicio' => '2026-01-15', 'vigencia_fin' => '2026-12-31',
        'cuota_monto' => 2000, 'periodicidad' => 'mensual',
    ]);
}

it('factura el periodo vigente con ISV', function () {
    $c = contratoConCuota();
    $f = app(FacturacionContratos::class)->facturar($c, Carbon::parse('2026-10-25'));

    expect($f->periodo->toDateString())->toBe('2026-10-15')
        ->and((float) $f->subtotal)->toBe(2000.0)
        ->and((float) $f->total)->toBe(round(2000 * (1 + config('tarifario.isv')), 2));
});

it('no duplica el periodo y sí crea el siguiente', function () {
    $c = contratoConCuota();
    $svc = app(FacturacionContratos::class);

    $svc->facturar($c, Carbon::parse('2026-10-25'));
    $svc->facturar($c, Carbon::parse('2026-10-26'));
    expect(Factura::where('contrato_id', $c->id)->count())->toBe(1);

    $svc->facturar($c, Carbon::parse('2026-11-16'));
    expect(Factura::where('contrato_id', $c->id)->count())->toBe(2);
});

it('trimestral cobra cada tres meses', function () {
    $c = contratoConCuota(['periodicidad' => 'trimestral']);

    expect(app(FacturacionContratos::class)->periodoActual($c, Carbon::parse('2026-10-25'))->toDateString())->toBe('2026-10-15');
    expect(app(FacturacionContratos::class)->periodoActual($c, Carbon::parse('2026-09-20'))->toDateString())->toBe('2026-07-15');
});

it('sin cuota o fuera de vigencia no factura', function () {
    $sin = contratoConCuota(['cuota_monto' => null]);
    $c = contratoConCuota();
    $svc = app(FacturacionContratos::class);

    expect($svc->facturar($sin, Carbon::parse('2026-10-25')))->toBeNull();
    expect($svc->facturar($c, Carbon::parse('2027-02-01')))->toBeNull();
});
