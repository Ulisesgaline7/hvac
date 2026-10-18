<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

function comoUsuarioApp(array $vars, Closure $fn): mixed
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
    $c->statement('TRUNCATE ordenes_trabajo, orden_trabajo_equipo, historial_estados_ot, documentos_tecnico, tecnicos, contrato_equipo, planes_preventivos, contratos, equipos, areas, sucursales, clientes, tipos_equipo, users RESTART IDENTITY CASCADE');

    $tipo = $c->table('tipos_equipo')->insertGetId(['nombre' => 'Split', 'familia' => 'aire_acondicionado', 'prefijo_codigo' => 'HVAC']);

    foreach (['a', 'b'] as $k) {
        $cli = $c->table('clientes')->insertGetId(['razon_social' => "Cliente $k"]);
        $suc = $c->table('sucursales')->insertGetId(['cliente_id' => $cli, 'nombre' => "Suc $k"]);
        $area = $c->table('areas')->insertGetId(['sucursal_id' => $suc, 'nombre' => 'Cocina']);
        $eq = $c->table('equipos')->insertGetId([
            'codigo' => "HVAC-$k", 'token_qr' => str_repeat($k, 32), 'area_id' => $area, 'tipo_equipo_id' => $tipo,
        ]);
        $user = $c->table('users')->insertGetId(['name' => "Tec $k", 'email' => "tec-$k@test.com", 'password' => 'x']);
        $tec = $c->table('tecnicos')->insertGetId(['user_id' => $user, 'estado_homologacion' => 'aprobado']);
        $c->table('documentos_tecnico')->insert(['tecnico_id' => $tec, 'tipo' => 'identificacion']);
        $ot = $c->table('ordenes_trabajo')->insertGetId([
            'numero' => "OT-$k", 'tipo' => 'correctivo', 'cliente_id' => $cli, 'sucursal_id' => $suc, 'tecnico_id' => $tec,
        ]);
        $c->table('orden_trabajo_equipo')->insert(['orden_trabajo_id' => $ot, 'equipo_id' => $eq]);

        $this->{"cli_$k"} = $cli;
        $this->{"user_$k"} = $user;
        $this->{"tec_$k"} = $tec;
        $this->{"ot_$k"} = $ot;
    }
});

it('un técnico solo ve sus OT, sus equipos y el contexto de esas OT', function () {
    comoUsuarioApp(['interno' => 'false', 'user' => $this->user_a], function ($c) {
        expect($c->table('ordenes_trabajo')->pluck('numero')->all())->toBe(['OT-a'])
            ->and($c->table('equipos')->pluck('codigo')->all())->toBe(['HVAC-a'])
            ->and($c->table('clientes')->count())->toBe(1)
            ->and($c->table('sucursales')->count())->toBe(1)
            ->and($c->table('areas')->count())->toBe(1)
            ->and($c->table('orden_trabajo_equipo')->count())->toBe(1)
            ->and($c->table('tecnicos')->count())->toBe(1)
            ->and($c->table('documentos_tecnico')->count())->toBe(1)
            ->and($c->table('contratos')->count())->toBe(0);
    });
});

it('un técnico suspendido deja de ver todo', function () {
    DB::connection('rls_owner')->table('tecnicos')->where('id', $this->tec_a)->update(['estado_homologacion' => 'suspendido']);

    comoUsuarioApp(['interno' => 'false', 'user' => $this->user_a], function ($c) {
        expect($c->table('ordenes_trabajo')->count())->toBe(0)
            ->and($c->table('equipos')->count())->toBe(0)
            ->and($c->table('clientes')->count())->toBe(0);
    });
});

it('un técnico no puede reasignar su OT a otro técnico', function () {
    comoUsuarioApp(['interno' => 'false', 'user' => $this->user_a], function ($c) {
        expect(fn () => $c->table('ordenes_trabajo')->where('id', $this->ot_a)->update(['tecnico_id' => $this->tec_b]))
            ->toThrow(QueryException::class);
    });
});

it('un técnico no puede modificar la OT de otro técnico', function () {
    comoUsuarioApp(['interno' => 'false', 'user' => $this->user_a], function ($c) {
        $afectadas = $c->table('ordenes_trabajo')->where('id', $this->ot_b)->update(['diagnostico' => 'intruso']);
        expect($afectadas)->toBe(0);
    });
});

it('un cliente ve sus OT y contratos pero no técnicos, y no puede escribir', function () {
    comoUsuarioApp(['interno' => 'false', 'cliente' => $this->cli_a], function ($c) {
        expect($c->table('ordenes_trabajo')->pluck('numero')->all())->toBe(['OT-a'])
            ->and($c->table('orden_trabajo_equipo')->count())->toBe(1)
            ->and($c->table('tecnicos')->count())->toBe(0)
            ->and($c->table('documentos_tecnico')->count())->toBe(0);

        expect(fn () => $c->table('ordenes_trabajo')->insert([
            'numero' => 'OT-x', 'tipo' => 'correctivo', 'cliente_id' => $this->cli_a,
            'sucursal_id' => $c->table('sucursales')->value('id'),
        ]))->toThrow(QueryException::class);
    });
});

it('el personal interno ve todas las OT y sin contexto no se ve nada', function () {
    comoUsuarioApp(['interno' => 'true'], fn ($c) => expect($c->table('ordenes_trabajo')->count())->toBe(2));
    comoUsuarioApp([], fn ($c) => expect($c->table('ordenes_trabajo')->count())->toBe(0)->and($c->table('tecnicos')->count())->toBe(0));
});
