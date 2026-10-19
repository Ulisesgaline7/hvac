<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\Factura;
use App\Models\Pago;
use App\Services\PlanCuotasService;
use Carbon\Carbon;

function facturaParaCuotas(float $total = 1150.00, ?string $vence = null): Factura
{
    [, $linea] = lineaConPlantilla();
    $subtotal = round($total / 1.15, 2);

    return Factura::create([
        'cliente_id' => $linea->ordenTrabajo->cliente_id,
        'fecha' => now()->toDateString(),
        'vence' => $vence ?? now()->toDateString(),
        'subtotal' => $subtotal,
        'isv' => round($total - $subtotal, 2),
        'total' => $total,
    ]);
}

it('las cuotas suman exactamente el total y la última absorbe el redondeo', function () {
    $cuotas = app(PlanCuotasService::class)->crear(facturaParaCuotas(1150.00), 3);

    expect($cuotas)->toHaveCount(3)
        ->and(round($cuotas->sum(fn ($c) => (float) $c->monto), 2))->toBe(1150.00)
        ->and((float) $cuotas->first()->monto)->toBe(383.33)
        ->and((float) $cuotas->last()->monto)->toBe(383.34);
});

it('las cuotas vencen mes a mes desde el primer vencimiento', function () {
    $cuotas = app(PlanCuotasService::class)->crear(facturaParaCuotas(), 3, Carbon::parse('2026-11-25'));

    expect($cuotas->map(fn ($c) => $c->vence->toDateString())->all())
        ->toBe(['2026-11-25', '2026-12-25', '2027-01-25']);
});

it('sin primer vencimiento usa el de la factura o un mes después de emitirla', function () {
    $svc = app(PlanCuotasService::class);

    $conCredito = $svc->crear(facturaParaCuotas(1000, now()->addDays(15)->toDateString()), 2);
    expect($conCredito->first()->vence->toDateString())->toBe(now()->addDays(15)->toDateString());

    $contraEntrega = $svc->crear(facturaParaCuotas(), 2);
    expect($contraEntrega->first()->vence->toDateString())->toBe(now()->addMonthNoOverflow()->toDateString());
});

it('rechaza un segundo plan sobre la misma factura', function () {
    $svc = app(PlanCuotasService::class);
    $f = facturaParaCuotas();
    $svc->crear($f, 3);

    expect(fn () => $svc->crear($f->fresh(), 4))->toThrow(DomainException::class);
});

it('rechaza menos de 2 o más de 12 cuotas', function () {
    $svc = app(PlanCuotasService::class);
    $f = facturaParaCuotas();

    expect(fn () => $svc->crear($f, 1))->toThrow(DomainException::class)
        ->and(fn () => $svc->crear($f, 13))->toThrow(DomainException::class);
});

it('rechaza facturas pagadas o anuladas', function () {
    $f = facturaParaCuotas();
    $f->update(['estado' => 'pagada']);

    expect(fn () => app(PlanCuotasService::class)->crear($f->fresh(), 3))->toThrow(DomainException::class);
});

it('lo cobrado cubre las cuotas en orden', function () {
    $svc = app(PlanCuotasService::class);
    $f = facturaParaCuotas(1150.00);
    $svc->crear($f, 3, now()->addDays(10));

    Pago::create(['factura_id' => $f->id, 'cliente_id' => $f->cliente_id, 'tipo' => 'pago', 'monto' => 500]);

    $filas = $svc->estado($f->fresh());

    expect($filas->pluck('estado')->all())->toBe(['pagada', 'parcial', 'pendiente'])
        ->and($filas[1]['saldo'])->toBe(266.66);
});

it('una cuota sin cubrir y con fecha pasada figura como vencida', function () {
    $svc = app(PlanCuotasService::class);
    $f = facturaParaCuotas();
    $svc->crear($f, 2, now()->subDay());

    expect($svc->estado($f->fresh())->first()['estado'])->toBe('vencida');
});
