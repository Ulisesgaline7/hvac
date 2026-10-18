<?php

use App\Enums\EstadoOt;
use App\Models\Area;
use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function nuevaOtCorrectiva(array $extra = []): OrdenTrabajo
{
    $cliente = Cliente::create(['razon_social' => 'Cliente ' . uniqid()]);
    $sucursal = Sucursal::create(['cliente_id' => $cliente->id, 'nombre' => 'Matriz']);

    return OrdenTrabajo::create(array_merge([
        'tipo' => 'correctivo', 'cliente_id' => $cliente->id, 'sucursal_id' => $sucursal->id,
        'fecha_programada' => now()->subDays(2)->toDateString(),
    ], $extra));
}

it('recorre el flujo completo y registra cada cambio con su motivo', function () {
    $ot = nuevaOtCorrectiva();

    foreach ([EstadoOt::Asignada, EstadoOt::EnProceso, EstadoOt::CompletadaTecnico, EstadoOt::EnRevision] as $e) {
        $ot->cambiarEstado($e);
    }
    $ot->cambiarEstado(EstadoOt::EnProceso, 'Falta foto de placa');
    expect($ot->fresh()->motivo_devolucion)->toBe('Falta foto de placa');

    $ot->cambiarEstado(EstadoOt::CompletadaTecnico);
    $ot->cambiarEstado(EstadoOt::EnRevision);
    $ot->cambiarEstado(EstadoOt::Cerrada);

    $ot = $ot->fresh();
    expect($ot->estado)->toBe(EstadoOt::Cerrada)
        ->and($ot->cerrada_en)->not->toBeNull()
        ->and($ot->tardia)->toBeTrue()
        ->and(DB::table('historial_estados_ot')->where('orden_trabajo_id', $ot->id)->where('motivo', 'Falta foto de placa')->count())->toBe(1);
});

it('rechaza transiciones inválidas y las OT cerradas no cambian', function () {
    $ot = nuevaOtCorrectiva();

    expect(fn () => $ot->cambiarEstado(EstadoOt::Cerrada))->toThrow(DomainException::class);

    $ot->cambiarEstado(EstadoOt::Cancelada, 'Cliente desistió');
    expect(fn () => $ot->cambiarEstado(EstadoOt::Pendiente))->toThrow(DomainException::class);
});
