<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

function comoAppRefri(array $vars, Closure $fn): mixed
{
    $c = DB::connection('rls_owner');
    $c->statement('SET ROLE hvac_app');
    $c->select("select set_config('app.es_interno', ?, false), set_config('app.cliente_id', ?, false), set_config('app.user_id', ?, false)", [
        $vars['interno'] ?? '', (string) ($vars['cliente'] ?? ''), (string) ($vars['user'] ?? ''),
    ]);

    try {
        return $fn($c);
    } finally {
        $c->statement('RESET ROLE');
        $c->select("select set_config('app.es_interno', '', false), set_config('app.cliente_id', '', false), set_config('app.user_id', '', false)");
    }
}

beforeEach(function () {
    static $migrado = false;

    config(['database.connections.rls_owner' => array_merge(config('database.connections.pgsql'), [
        'database' => 'hvac_manager_test',
    ])]);

    try {
        DB::connection('rls_owner')->getPdo();
    } catch (Throwable $e) {
        $this->markTestSkipped('Postgres de pruebas no disponible: ' . $e->getMessage());
    }

    if (! $migrado) {
        Artisan::call('migrate:fresh', ['--database' => 'rls_owner', '--force' => true]);
        $migrado = true;
    }

    $c = DB::connection('rls_owner');
    $c->statement('TRUNCATE movimientos_refrigerante, cilindros_refrigerante, refrigerantes, fichas_tecnicas, plantilla_versiones, plantillas_formulario, historial_estados_ot, ordenes_trabajo, orden_trabajo_equipo, documentos_tecnico, tecnicos, contrato_equipo, planes_preventivos, contratos, equipos, areas, sucursales, clientes, tipos_equipo, users RESTART IDENTITY CASCADE');

    $tipo = $c->table('tipos_equipo')->insertGetId(['nombre' => 'Split', 'familia' => 'aire_acondicionado', 'prefijo_codigo' => 'EQ']);
    $ref = $c->table('refrigerantes')->insertGetId(['nombre' => 'R-410A']);
    $cil = $c->table('cilindros_refrigerante')->insertGetId(['codigo' => 'CIL-1', 'refrigerante_id' => $ref, 'peso_inicial_kg' => 10]);

    $this->d = ['cil' => $cil, 'ref' => $ref];

    foreach (['a', 'b'] as $k) {
        $cli = $c->table('clientes')->insertGetId(['razon_social' => "Cliente $k"]);
        $suc = $c->table('sucursales')->insertGetId(['cliente_id' => $cli, 'nombre' => "Suc $k"]);
        $area = $c->table('areas')->insertGetId(['sucursal_id' => $suc, 'nombre' => 'Cocina']);
        $eq = $c->table('equipos')->insertGetId(['codigo' => "EQ-$k", 'token_qr' => str_repeat($k, 32), 'area_id' => $area, 'tipo_equipo_id' => $tipo]);
        $user = $c->table('users')->insertGetId(['name' => "Tec $k", 'email' => "tec-$k@test.com", 'password' => 'x']);
        $tec = $c->table('tecnicos')->insertGetId(['user_id' => $user, 'estado_homologacion' => 'aprobado']);

        $this->d[$k] = ['cli' => $cli, 'user' => $user, 'tec' => $tec];

        foreach (['abierta' => 'en_proceso', 'cerrada' => 'cerrada'] as $clave => $estado) {
            $ot = $c->table('ordenes_trabajo')->insertGetId([
                'numero' => "OT-$k-$clave", 'tipo' => 'correctivo', 'estado' => $estado,
                'cliente_id' => $cli, 'sucursal_id' => $suc, 'tecnico_id' => $tec,
            ]);
            $linea = $c->table('orden_trabajo_equipo')->insertGetId(['orden_trabajo_id' => $ot, 'equipo_id' => $eq]);
            $c->table('movimientos_refrigerante')->insert([
                'orden_trabajo_id' => $ot, 'orden_trabajo_equipo_id' => $linea,
                'cilindro_id' => $cil, 'tipo' => 'carga', 'cantidad_kg' => 1,
            ]);
        }
    }
});

it('el cliente solo ve movimientos de sus OT cerradas y nunca los cilindros', function () {
    comoAppRefri(['cliente' => $this->d['a']['cli']], function ($c) {
        expect($c->table('movimientos_refrigerante')->count())->toBe(1)
            ->and($c->table('cilindros_refrigerante')->count())->toBe(0);
    });
});

it('el técnico ve los movimientos de sus OT y los cilindros, no los de otro técnico', function () {
    comoAppRefri(['user' => $this->d['a']['user']], function ($c) {
        expect($c->table('movimientos_refrigerante')->count())->toBe(2)
            ->and($c->table('cilindros_refrigerante')->count())->toBe(1);
    });
});

it('el técnico no puede registrar movimientos en la OT de otro técnico', function () {
    $o = DB::connection('rls_owner');
    $otB = $o->table('ordenes_trabajo')->where('numero', 'OT-b-abierta')->value('id');
    $lineaB = $o->table('orden_trabajo_equipo')->where('orden_trabajo_id', $otB)->value('id');

    comoAppRefri(['user' => $this->d['a']['user']], function ($c) use ($otB, $lineaB) {
        expect(fn () => $c->table('movimientos_refrigerante')->insert([
            'orden_trabajo_id' => $otB, 'orden_trabajo_equipo_id' => $lineaB,
            'cilindro_id' => $this->d['cil'], 'tipo' => 'carga', 'cantidad_kg' => 1,
        ]))->toThrow(Illuminate\Database\QueryException::class);
    });
});

it('el cliente no puede escribir cilindros', function () {
    comoAppRefri(['cliente' => $this->d['a']['cli']], function ($c) {
        expect(fn () => $c->table('cilindros_refrigerante')->insert([
            'codigo' => 'CIL-X', 'refrigerante_id' => $this->d['ref'], 'peso_inicial_kg' => 5,
        ]))->toThrow(Illuminate\Database\QueryException::class);
    });
});

it('el personal interno ve todo y sin contexto no se ve nada', function () {
    comoAppRefri(['interno' => 'true'], function ($c) {
        expect($c->table('movimientos_refrigerante')->count())->toBe(4)
            ->and($c->table('cilindros_refrigerante')->count())->toBe(1);
    });

    comoAppRefri([], function ($c) {
        expect($c->table('movimientos_refrigerante')->count())->toBe(0)
            ->and($c->table('cilindros_refrigerante')->count())->toBe(0);
    });
});
