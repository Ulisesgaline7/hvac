<?php

use App\Enums\EstadoOt;
use App\Enums\TipoOt;
use App\Models\Area;
use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\ContratoEquipo;
use App\Models\Equipo;
use App\Models\OrdenTrabajo;
use App\Models\Sucursal;
use App\Models\TipoEquipo;
use App\Services\GeneradorCalendario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function montarContratoPreventivo(array $contrato = [], int $equipos = 1, string $frecuencia = 'trimestral'): array
{
    $cliente = Cliente::create(['razon_social' => 'Cliente ' . uniqid()]);
    $sucursal = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => 'Matriz']);
    $area = Area::create(['sucursal_id' => $sucursal->id, 'nombre' => 'Cocina']);
    $tipo = TipoEquipo::firstOrCreate(['prefijo_codigo' => 'HVAC'], ['nombre' => 'Split', 'familia' => 'aire_acondicionado']);

    $contrato = Contrato::create(array_merge([
        'cliente_id' => $cliente->id,
        'tipo' => 'preventivo_recurrente',
        'vigencia_inicio' => '2026-11-01',
        'vigencia_fin' => '2027-04-30',
        'estado' => 'activo',
        'anticipacion_dias' => 30,
    ], $contrato));

    for ($i = 0; $i < $equipos; $i++) {
        $equipo = Equipo::create(['area_id' => $area->id, 'tipo_equipo_id' => $tipo->id]);
        ContratoEquipo::create([
            'contrato_id' => $contrato->id,
            'equipo_id' => $equipo->id,
            'frecuencia' => $frecuencia,
            'fecha_inicio' => $contrato->vigencia_inicio->toDateString(),
        ]);
    }

    return compact('contrato', 'cliente', 'sucursal');
}

beforeEach(fn () => Carbon::setTestNow('2026-10-18 09:00:00'));
afterEach(fn () => Carbon::setTestNow());

it('crea solo las OT dentro de la ventana y no duplica al repetir', function () {
    ['contrato' => $contrato] = montarContratoPreventivo();
    $gen = app(GeneradorCalendario::class);

    expect($gen->generar($contrato))->toBe(1)
        ->and($gen->generar($contrato))->toBe(0)
        ->and(OrdenTrabajo::count())->toBe(1);

    $ot = OrdenTrabajo::first();
    expect($ot->tipo)->toBe(TipoOt::Preventivo)
        ->and($ot->estado)->toBe(EstadoOt::Pendiente)
        ->and($ot->fecha_programada->toDateString())->toBe('2026-11-01')
        ->and($ot->numero)->toStartWith('OT-2026-')
        ->and($ot->contrato_id)->toBe($contrato->id);

    Carbon::setTestNow('2027-01-15');
    expect($gen->generar($contrato->fresh()))->toBe(1)
        ->and(OrdenTrabajo::count())->toBe(2);
});

it('un contrato de instalación o correctivo no genera calendario', function () {
    foreach (['instalacion', 'correctivo'] as $tipo) {
        ['contrato' => $contrato] = montarContratoPreventivo(['tipo' => $tipo]);
        expect(app(GeneradorCalendario::class)->generar($contrato))->toBe(0);
    }

    expect(OrdenTrabajo::count())->toBe(0);
});

it('agrupa en una sola OT los equipos de la misma sucursal y fecha', function () {
    ['contrato' => $contrato] = montarContratoPreventivo([], 2);

    expect(app(GeneradorCalendario::class)->generar($contrato))->toBe(1)
        ->and(OrdenTrabajo::first()->equipos)->toHaveCount(2);
});

it('registra el estado inicial en el historial de la OT', function () {
    ['contrato' => $contrato] = montarContratoPreventivo();
    app(GeneradorCalendario::class)->generar($contrato);

    $fila = DB::table('historial_estados_ot')->first();
    expect($fila->estado_nuevo)->toBe('pendiente')->and($fila->estado_anterior)->toBeNull();
});

it('al recalcular cancela las pendientes que sobran y no toca las iniciadas', function () {
    ['contrato' => $contrato] = montarContratoPreventivo(
        ['anticipacion_dias' => 60, 'vigencia_fin' => '2027-10-31'], 1, 'mensual'
    );
    $gen = app(GeneradorCalendario::class);

    expect($gen->generar($contrato))->toBe(2);

    OrdenTrabajo::orderBy('fecha_original')->first()->update(['estado' => 'en_proceso']);
    ContratoEquipo::where('contrato_id', $contrato->id)->update(['frecuencia' => 'semestral']);

    $resultado = $gen->recalcular($contrato->fresh());

    expect($resultado)->toBe(['canceladas' => 1, 'creadas' => 0])
        ->and(OrdenTrabajo::where('estado', 'cancelada')->count())->toBe(1)
        ->and(OrdenTrabajo::where('estado', 'en_proceso')->count())->toBe(1);
});

it('al cancelar el contrato se cancelan las OT pendientes futuras', function () {
    ['contrato' => $contrato] = montarContratoPreventivo();
    $gen = app(GeneradorCalendario::class);
    $gen->generar($contrato);

    $contrato->update(['estado' => 'cancelado']);

    expect($gen->recalcular($contrato->fresh()))->toBe(['canceladas' => 1, 'creadas' => 0]);
});
