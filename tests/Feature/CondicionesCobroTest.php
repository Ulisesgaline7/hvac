<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\Cotizacion;
use App\Services\FacturacionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

function otDePrueba()
{
    [, $linea] = lineaConPlantilla();

    return $linea->ordenTrabajo->fresh();
}

it('sin valores en OT ni cotización, el crédito sale de la config', function () {
    config(['cobros.dias_credito' => 15]);

    expect(app(FacturacionService::class)->diasCredito(otDePrueba()))->toBe(15);
});

it('la cotización manda sobre la config', function () {
    config(['cobros.dias_credito' => 0]);

    expect(app(FacturacionService::class)->diasCredito(otDePrueba(), new Cotizacion(['dias_credito' => 30])))->toBe(30);
});

it('la OT manda sobre la cotización', function () {
    $ot = otDePrueba();
    $ot->update(['dias_credito' => 15]);

    expect(app(FacturacionService::class)->diasCredito($ot->fresh(), new Cotizacion(['dias_credito' => 30])))->toBe(15);
});

it('el % de anticipo sigue la misma cadena OT, cotización, config', function () {
    $svc = app(FacturacionService::class);
    $ot = otDePrueba(); // correctivo: config = 0

    expect($svc->anticipoPct($ot))->toBe(0.0)
        ->and($svc->anticipoPct($ot, new Cotizacion(['anticipo_pct' => 30])))->toBe(30.0);

    $ot->update(['anticipo_pct' => 40]);
    expect($svc->anticipoPct($ot->fresh(), new Cotizacion(['anticipo_pct' => 30])))->toBe(40.0);
});

it('la factura al cerrar usa el crédito negociado del trabajo', function () {
    $ot = otDePrueba();
    $ot->update(['precio_estimado' => 1000, 'dias_credito' => 30]);

    $factura = app(FacturacionService::class)->generarDesdeOt($ot->fresh());

    expect($factura)->not->toBeNull()
        ->and(Carbon::parse($factura->vence)->toDateString())->toBe(now()->addDays(30)->toDateString());
});

it('la base de datos rechaza días de crédito fuera de 15 a 30', function () {
    $ot = otDePrueba();

    expect(fn () => $ot->update(['dias_credito' => 10]))->toThrow(Illuminate\Database\QueryException::class);
})->skip(fn () => DB::getDriverName() !== 'pgsql', 'Requiere PostgreSQL');
