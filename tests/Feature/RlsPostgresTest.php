<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

function comoApp(array $vars, Closure $fn): mixed
{
    $c = DB::connection('rls_owner');
    $c->statement('SET ROLE hvac_app');
    $c->select("select set_config('app.es_interno', ?, false), set_config('app.cliente_id', ?, false)", [
        $vars['interno'] ?? '', (string) ($vars['cliente'] ?? ''),
    ]);

    try {
        return $fn($c);
    } finally {
        $c->statement('RESET ROLE');
        $c->select("select set_config('app.es_interno', '', false), set_config('app.cliente_id', '', false)");
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
    $c->statement('TRUNCATE equipos, areas, sucursales, clientes, tipos_equipo RESTART IDENTITY CASCADE');

    $tipo = $c->table('tipos_equipo')->insertGetId(['nombre' => 'Split', 'familia' => 'aire_acondicionado', 'prefijo_codigo' => 'HVAC']);

    foreach (['a' => 'Cliente A', 'b' => 'Cliente B'] as $k => $nombre) {
        $cli = $c->table('clientes')->insertGetId(['razon_social' => $nombre]);
        $suc = $c->table('sucursales')->insertGetId(['cliente_id' => $cli, 'nombre' => "Suc $nombre"]);
        $area = $c->table('areas')->insertGetId(['sucursal_id' => $suc, 'nombre' => 'Cocina']);
        $c->table('equipos')->insert([
            'codigo' => "HVAC-$k", 'token_qr' => str_repeat($k, 32), 'area_id' => $area, 'tipo_equipo_id' => $tipo,
        ]);
        $this->{"cliente_$k"} = $cli;
    }
});

it('el rol de la app no es superusuario ni salta RLS', function () {
    $rol = DB::connection('rls_owner')->selectOne("select rolsuper, rolbypassrls from pg_roles where rolname = 'hvac_app'");

    expect($rol->rolsuper)->toBeFalse()->and($rol->rolbypassrls)->toBeFalse();
});

it('un cliente solo ve sus filas aunque consulte las tablas sin scopes', function () {
    comoApp(['interno' => 'false', 'cliente' => $this->cliente_a], function ($c) {
        expect($c->table('clientes')->count())->toBe(1)
            ->and($c->table('sucursales')->count())->toBe(1)
            ->and($c->table('areas')->count())->toBe(1)
            ->and($c->table('equipos')->count())->toBe(1)
            ->and($c->table('equipos')->where('codigo', 'HVAC-b')->count())->toBe(0)
            ->and($c->table('clientes')->where('id', $this->cliente_b)->count())->toBe(0);
    });
});

it('el personal interno ve todo', function () {
    comoApp(['interno' => 'true'], function ($c) {
        expect($c->table('clientes')->count())->toBe(2)->and($c->table('equipos')->count())->toBe(2);
    });
});

it('sin variables no se ve nada', function () {
    comoApp([], function ($c) {
        expect($c->table('clientes')->count())->toBe(0)->and($c->table('equipos')->count())->toBe(0);
    });
});

it('un cliente no puede escribir filas de otro cliente', function () {
    comoApp(['interno' => 'false', 'cliente' => $this->cliente_a], function ($c) {
        expect(fn () => $c->table('sucursales')->insert(['cliente_id' => $this->cliente_b, 'nombre' => 'Intruso']))
            ->toThrow(Illuminate\Database\QueryException::class);
    });

    expect(DB::connection('rls_owner')->table('sucursales')->where('nombre', 'Intruso')->count())->toBe(0);
});
