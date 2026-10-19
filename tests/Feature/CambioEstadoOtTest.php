<?php

use App\Enums\EstadoOt;
use App\Models\Area;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoEquipo;
use App\Models\PlantillaFormulario;
use App\Models\Sucursal;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\FichaService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** OT correctiva con un equipo y la ficha marcada como completa (usa el helper de FormulariosTest). */
function otListaParaCerrar()
{
    [, $linea] = lineaConPlantilla();
    $ficha = app(FichaService::class)->obtenerOCrear($linea);
    $ficha->update(['completa' => true, 'alertas' => []]);

    $ot = $linea->ordenTrabajo;
    $ot->update(['fecha_programada' => now()->subDays(2)->toDateString()]);

    return $ot->fresh();
}

it('recorre el flujo completo y registra cada cambio con su motivo', function () {
    $ot = otListaParaCerrar();

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

it('rechaza transiciones inválidas y las OT canceladas no cambian', function () {
    $ot = otListaParaCerrar();

    expect(fn () => $ot->cambiarEstado(EstadoOt::Cerrada))->toThrow(DomainException::class);

    $ot->cambiarEstado(EstadoOt::Cancelada, 'Cliente desistió');
    expect(fn () => $ot->fresh()->cambiarEstado(EstadoOt::Pendiente))->toThrow(DomainException::class);
});

it('no deja completar una OT sin equipos ni fichas completas', function () {
    $ot = otListaParaCerrar();
    $ot->lineas()->first()->ficha->update(['completa' => false]);

    foreach ([EstadoOt::Asignada, EstadoOt::EnProceso] as $e) {
        $ot->cambiarEstado($e);
    }

    expect(fn () => $ot->cambiarEstado(EstadoOt::CompletadaTecnico))->toThrow(DomainException::class);
});

it('un técnico no puede cerrar ni devolver una OT; el coordinador sí', function () {
    $this->seed(RolesSeeder::class);
    $ot = otListaParaCerrar();
    DB::table('ordenes_trabajo')->where('id', $ot->id)->update(['estado' => 'en_revision']);

    $tecnico = User::factory()->create();
    $tecnico->assignRole('tecnico');
    $this->actingAs($tecnico);
    expect(fn () => $ot->fresh()->cambiarEstado(EstadoOt::Cerrada))->toThrow(DomainException::class, 'Solo coordinador')
        ->and(fn () => $ot->fresh()->cambiarEstado(EstadoOt::EnProceso, 'x'))->toThrow(DomainException::class, 'Solo coordinador');

    $coord = User::factory()->create();
    $coord->assignRole('coordinador');
    $this->actingAs($coord);
    $ot->fresh()->cambiarEstado(EstadoOt::Cerrada);

    expect($ot->fresh()->estado)->toBe(EstadoOt::Cerrada);
});

it('una OT cerrada o cancelada es de solo lectura', function () {
    $ot = otListaParaCerrar();

    // Cancelada
    $cancelada = OrdenTrabajo::create([
        'tipo' => 'correctivo',
        'cliente_id' => $ot->cliente_id,
        'sucursal_id' => $ot->sucursal_id,
    ]);
    $cancelada->cambiarEstado(EstadoOt::Cancelada, 'prueba');

    $cancelada->prioridad = 'urgente';
    expect(fn () => $cancelada->save())->toThrow(DomainException::class, 'cancelada');

    // Cerrada: forzamos el estado por SQL para no depender del flujo completo
    DB::table('ordenes_trabajo')->where('id', $ot->id)->update(['estado' => EstadoOt::Cerrada->value]);
    $cerrada = OrdenTrabajo::find($ot->id);

    $cerrada->prioridad = 'urgente';
    expect(fn () => $cerrada->save())->toThrow(DomainException::class, 'cerrada');

    expect(DB::table('ordenes_trabajo')->where('id', $ot->id)->value('prioridad'))->not->toBe('urgente');
});

/** Lleva la OT hasta «En revisión» (último paso antes de cerrar). */
function llevarARevision(OrdenTrabajo $ot): OrdenTrabajo
{
    foreach ([EstadoOt::Asignada, EstadoOt::EnProceso, EstadoOt::CompletadaTecnico, EstadoOt::EnRevision] as $e) {
        $ot->cambiarEstado($e);
    }

    return $ot;
}

function firmarActa(OrdenTrabajo $ot): void
{
    $acta = App\Models\ActaConformidad::create(['orden_trabajo_id' => $ot->id, 'recibe_nombre' => 'Cliente Prueba']);
    $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    $acta->addMediaFromBase64($png, ['image/png'])->usingFileName('firma_cliente.png')->toMediaCollection('firma_cliente', 'local');
}

it('instalación y emergencia no cierran sin acta firmada por el cliente', function (string $tipo) {
    $ot = otListaParaCerrar();
    $ot->update(['tipo' => $tipo]);
    llevarARevision($ot->fresh());

    expect(fn () => $ot->fresh()->cambiarEstado(EstadoOt::Cerrada))
        ->toThrow(DomainException::class, 'acta de conformidad firmada');

    expect($ot->fresh()->estado)->toBe(EstadoOt::EnRevision);
})->with(['instalacion', 'emergencia']);

it('un acta sin firma del cliente tampoco permite cerrar', function () {
    $ot = otListaParaCerrar();
    $ot->update(['tipo' => 'instalacion']);
    llevarARevision($ot->fresh());
    App\Models\ActaConformidad::create(['orden_trabajo_id' => $ot->id, 'recibe_nombre' => 'Sin firma']);

    expect(fn () => $ot->fresh()->cambiarEstado(EstadoOt::Cerrada))->toThrow(DomainException::class);
});

it('con el acta firmada por el cliente la instalación se cierra', function () {
    Illuminate\Support\Facades\Storage::fake('local');

    $ot = otListaParaCerrar();
    $ot->update(['tipo' => 'instalacion']);
    llevarARevision($ot->fresh());
    firmarActa($ot);

    $ot->fresh()->cambiarEstado(EstadoOt::Cerrada);

    expect($ot->fresh()->estado)->toBe(EstadoOt::Cerrada);
});

it('un correctivo se cierra sin acta', function () {
    $ot = llevarARevision(otListaParaCerrar());

    $ot->cambiarEstado(EstadoOt::Cerrada);

    expect($ot->fresh()->estado)->toBe(EstadoOt::Cerrada);
});
