<?php

use App\Enums\EstadoOt;
use App\Models\Tecnico;
use App\Models\User;
use App\Services\CuadrillaService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function tecnicoCuadrilla(string $estado = 'aprobado'): Tecnico
{
    $u = User::create(['name' => 'Téc ' . uniqid(), 'email' => uniqid() . '@test.com', 'password' => 'x']);

    return Tecnico::create(['user_id' => $u->id, 'estado_homologacion' => $estado]);
}

function otParaCuadrilla()
{
    [, $linea] = lineaConPlantilla();

    return $linea->ordenTrabajo->fresh();
}

it('asigna líder y miembros, deja al líder como técnico de la OT y la pasa a Asignada', function () {
    $ot = otParaCuadrilla();
    [$lider, $ayudante, $segundo] = [tecnicoCuadrilla(), tecnicoCuadrilla(), tecnicoCuadrilla()];

    $ot = app(CuadrillaService::class)->asignar($ot, $lider->id, [$ayudante->id => 'ayudante', $segundo->id => 'tecnico']);

    expect($ot->tecnico_id)->toBe($lider->id)
        ->and($ot->estado)->toBe(EstadoOt::Asignada)
        ->and($ot->cuadrilla)->toHaveCount(3)
        ->and($ot->cuadrilla->where('pivot.rol', 'lider')->count())->toBe(1)
        ->and($ot->cuadrilla->firstWhere('id', $ayudante->id)->pivot->rol)->toBe('ayudante');
});

it('reasignar reemplaza la cuadrilla y no duplica miembros', function () {
    $ot = otParaCuadrilla();
    [$a, $b] = [tecnicoCuadrilla(), tecnicoCuadrilla()];
    $servicio = app(CuadrillaService::class);

    $servicio->asignar($ot, $a->id, [$b->id => 'tecnico']);
    $ot = $servicio->asignar($ot->fresh(), $b->id);

    expect($ot->tecnico_id)->toBe($b->id)
        ->and($ot->cuadrilla)->toHaveCount(1)
        ->and($ot->cuadrilla->first()->id)->toBe($b->id);
});

it('rechaza técnicos no aprobados y roles inválidos', function () {
    $ot = otParaCuadrilla();
    $servicio = app(CuadrillaService::class);

    expect(fn () => $servicio->asignar($ot, tecnicoCuadrilla('pendiente')->id))->toThrow(DomainException::class, 'aprobados');
    expect(fn () => $servicio->asignar($ot, tecnicoCuadrilla()->id, [tecnicoCuadrilla()->id => 'jefe']))->toThrow(DomainException::class, 'Rol');
});

it('una instalación exige al menos 2 personas', function () {
    $ot = otParaCuadrilla();
    $ot->update(['tipo' => 'instalacion']);
    $servicio = app(CuadrillaService::class);

    expect(fn () => $servicio->asignar($ot->fresh(), tecnicoCuadrilla()->id))->toThrow(DomainException::class, 'al menos 2');

    $ot = $servicio->asignar($ot->fresh(), tecnicoCuadrilla()->id, [tecnicoCuadrilla()->id => 'ayudante']);
    expect($ot->cuadrilla)->toHaveCount(2);
});

it('sugiere la composición del tarifario', function () {
    $s = app(CuadrillaService::class);

    expect($s->composicionSugerida(4, true))->toBe(['tecnicos' => 4, 'ayudantes' => 2, 'supervisor' => true])
        ->and($s->composicionSugerida(1, true))->toBe(['tecnicos' => 1, 'ayudantes' => 1, 'supervisor' => false])
        ->and($s->composicionSugerida(3))->toBe(['tecnicos' => 3, 'ayudantes' => 0, 'supervisor' => false]);
});

it('asignar el técnico desde el formulario sincroniza al líder en la cuadrilla', function () {
    $ot = otParaCuadrilla();
    [$a, $b] = [tecnicoCuadrilla(), tecnicoCuadrilla()];

    $ot->update(['tecnico_id' => $a->id]);
    expect($ot->fresh()->cuadrilla->pluck('pivot.rol', 'id')->all())->toBe([$a->id => 'lider']);

    $ot->fresh()->update(['tecnico_id' => $b->id]);
    expect($ot->fresh()->cuadrilla->pluck('pivot.rol', 'id')->all())->toBe([$b->id => 'lider']);
});
