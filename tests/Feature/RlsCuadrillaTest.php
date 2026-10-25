<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

function comoTecnicoRls(int $userId, Closure $fn): mixed
{
    $c = DB::connection('rls_owner');
    $c->statement('SET ROLE hvac_app');
    $c->select("select set_config('app.es_interno', 'false', false), set_config('app.cliente_id', '', false), set_config('app.user_id', ?, false)", [(string) $userId]);

    try {
        return $fn($c);
    } finally {
        $c->statement('RESET ROLE');
        $c->select("select set_config('app.es_interno', '', false), set_config('app.cliente_id', '', false), set_config('app.user_id', '', false)");
    }
}

beforeEach(function () {
    config(['database.connections.rls_owner' => array_merge(config('database.connections.pgsql'), [
        'database' => 'hvac_manager_test',
    ])]);

    try {
        DB::connection('rls_owner')->getPdo();
    } catch (Throwable $e) {
        $this->markTestSkipped('Postgres de pruebas no disponible: ' . $e->getMessage());
    }

    Artisan::call('migrate', ['--database' => 'rls_owner', '--force' => true]);

    $c = DB::connection('rls_owner');
    $c->statement('TRUNCATE media, actas_conformidad, trabajos_adicionales, fichas_tecnicas, plantilla_versiones, plantillas_formulario, historial_estados_ot, orden_trabajo_tecnico, ordenes_trabajo, orden_trabajo_equipo, documentos_tecnico, tecnicos, contrato_equipo, planes_preventivos, contratos, equipos, areas, sucursales, clientes, tipos_equipo, users RESTART IDENTITY CASCADE');

    $tipo = $c->table('tipos_equipo')->insertGetId(['nombre' => 'Split', 'familia' => 'aire_acondicionado', 'prefijo_codigo' => 'HVAC']);
    $plantilla = $c->table('plantillas_formulario')->insertGetId(['nombre' => 'P', 'familia' => 'aire_acondicionado']);
    $version = $c->table('plantilla_versiones')->insertGetId(['plantilla_id' => $plantilla, 'version' => 1, 'definicion' => '[]']);

    $cli = $c->table('clientes')->insertGetId(['razon_social' => 'Cliente C']);
    $suc = $c->table('sucursales')->insertGetId(['cliente_id' => $cli, 'nombre' => 'Suc C']);
    $area = $c->table('areas')->insertGetId(['sucursal_id' => $suc, 'nombre' => 'Cocina']);
    $eq = $c->table('equipos')->insertGetId(['codigo' => 'HVAC-C', 'token_qr' => str_repeat('c', 32), 'area_id' => $area, 'tipo_equipo_id' => $tipo]);

    $this->u = [];
    $this->t = [];
    foreach (['lider', 'miembro', 'ajeno'] as $k) {
        $this->u[$k] = $c->table('users')->insertGetId(['name' => "Tec $k", 'email' => "$k@test.com", 'password' => 'x']);
        $this->t[$k] = $c->table('tecnicos')->insertGetId(['user_id' => $this->u[$k], 'estado_homologacion' => 'aprobado']);
    }

    $ot = $c->table('ordenes_trabajo')->insertGetId([
        'numero' => 'OT-C-1', 'tipo' => 'correctivo', 'estado' => 'en_proceso',
        'cliente_id' => $cli, 'sucursal_id' => $suc, 'tecnico_id' => $this->t['lider'],
    ]);
    $linea = $c->table('orden_trabajo_equipo')->insertGetId(['orden_trabajo_id' => $ot, 'equipo_id' => $eq]);
    $ficha = $c->table('fichas_tecnicas')->insertGetId(['orden_trabajo_id' => $ot, 'orden_trabajo_equipo_id' => $linea, 'plantilla_version_id' => $version]);

    $c->table('orden_trabajo_tecnico')->insert([
        ['orden_trabajo_id' => $ot, 'tecnico_id' => $this->t['lider'], 'rol' => 'lider'],
        ['orden_trabajo_id' => $ot, 'tecnico_id' => $this->t['miembro'], 'rol' => 'tecnico'],
    ]);

    $this->d = compact('ot', 'linea', 'ficha', 'cli', 'suc', 'eq');
});

it('el líder y los miembros ven la OT y su contexto; un técnico ajeno no ve nada', function () {
    foreach (['lider', 'miembro'] as $k) {
        comoTecnicoRls($this->u[$k], function ($c) use ($k) {
            expect($c->table('ordenes_trabajo')->count())->toBe(1, "OT de $k")
                ->and($c->table('orden_trabajo_equipo')->count())->toBe(1)
                ->and($c->table('fichas_tecnicas')->count())->toBe(1)
                ->and($c->table('clientes')->count())->toBe(1)
                ->and($c->table('sucursales')->count())->toBe(1)
                ->and($c->table('equipos')->count())->toBe(1)
                ->and($c->table('areas')->count())->toBe(1);
        });
    }

    comoTecnicoRls($this->u['ajeno'], function ($c) {
        foreach (['ordenes_trabajo', 'orden_trabajo_equipo', 'fichas_tecnicas', 'clientes', 'sucursales', 'equipos', 'areas'] as $tabla) {
            expect($c->table($tabla)->count())->toBe(0, "tabla $tabla");
        }
    });
});

it('un miembro llena fichas y crea actas, pero no modifica la OT ni actúa en otra', function () {
    comoTecnicoRls($this->u['miembro'], function ($c) {
        expect($c->table('fichas_tecnicas')->where('id', $this->d['ficha'])->update(['completa' => true]))->toBe(1)
            ->and($c->table('actas_conformidad')->insert(['orden_trabajo_id' => $this->d['ot']]))->toBeTrue()
            ->and($c->table('ordenes_trabajo')->where('id', $this->d['ot'])->update(['descripcion' => 'cambio']))->toBe(0);
    });

    comoTecnicoRls($this->u['ajeno'], function ($c) {
        expect(fn () => $c->table('actas_conformidad')->insert(['orden_trabajo_id' => $this->d['ot']]))->toThrow(QueryException::class);
    });
});

it('al quitarlo de la cuadrilla el técnico pierde el acceso', function () {
    DB::connection('rls_owner')->table('orden_trabajo_tecnico')->where('tecnico_id', $this->t['miembro'])->delete();

    comoTecnicoRls($this->u['miembro'], function ($c) {
        expect($c->table('ordenes_trabajo')->count())->toBe(0)
            ->and($c->table('clientes')->count())->toBe(0);
    });

    comoTecnicoRls($this->u['lider'], fn ($c) => expect($c->table('ordenes_trabajo')->count())->toBe(1));
});

it('registro en campo: el técnico no inserta equipos por sí solo; en modo interno sí, y después los ve', function () {
    $dueno = DB::connection('rls_owner');
    $tipo = $dueno->table('tipos_equipo')->value('id');
    $area = $dueno->table('areas')->value('id');

    foreach (['lider' => 'n', 'miembro' => 'm'] as $k => $letra) {
        comoTecnicoRls($this->u[$k], function ($c) use ($k, $letra, $tipo, $area) {
            $fila = ['codigo' => "HVAC-N-$letra", 'token_qr' => str_repeat($letra, 32), 'area_id' => $area, 'tipo_equipo_id' => $tipo];

            // Con sus propios permisos, el técnico no puede crear equipos.
            expect(fn () => $c->table('equipos')->insert($fila))->toThrow(QueryException::class);

            // Sí puede comprobar su pertenencia a la OT.
            expect($c->table('orden_trabajo_tecnico')->where('orden_trabajo_id', $this->d['ot'])->count())->toBe(1);

            $antes = $c->table('equipos')->count();

            // Lo que hace comoInterno: contexto interno, crear, restaurar el contexto previo.
            $c->select("select set_config('app.es_interno', 'true', false)");
            $id = $c->table('equipos')->insertGetId($fila);
            $c->table('orden_trabajo_equipo')->insert(['orden_trabajo_id' => $this->d['ot'], 'equipo_id' => $id, 'tipo_equipo_id' => $tipo, 'area_id' => $area]);
            $c->select("select set_config('app.es_interno', 'false', false)");

            // Restaurado el contexto, el técnico ve el equipo nuevo por estar en su OT.
            expect($c->table('equipos')->count())->toBe($antes + 1)
                ->and($c->table('equipos')->where('id', $id)->exists())->toBeTrue();
        });
    }

    // Un técnico ajeno a la OT no ve ninguno de los equipos creados.
    comoTecnicoRls($this->u['ajeno'], function ($c) {
        expect($c->table('equipos')->count())->toBe(0);
    });
});
