<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

function comoAppInformes(array $vars, Closure $fn): mixed
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
    $c->statement('TRUNCATE informes, ordenes_trabajo, sucursales, clientes RESTART IDENTITY CASCADE');

    $this->c = [];
    foreach (['a', 'b'] as $k) {
        $cli = $c->table('clientes')->insertGetId(['razon_social' => "Cliente $k"]);
        $suc = $c->table('sucursales')->insertGetId(['cliente_id' => $cli, 'nombre' => "Suc $k"]);
        $this->c[$k] = $cli;

        $casos = $k === 'a' ? ['pub' => 'publicado', 'borr' => 'borrador'] : ['pub' => 'publicado'];
        foreach ($casos as $clave => $estado) {
            $ot = $c->table('ordenes_trabajo')->insertGetId([
                'numero' => "OT-$k-$clave", 'tipo' => 'correctivo', 'estado' => 'cerrada', 'cliente_id' => $cli, 'sucursal_id' => $suc,
            ]);
            $c->table('informes')->insert([
                'orden_trabajo_id' => $ot, 'estado' => $estado, 'ruta_pdf' => 'x',
                'token_verificacion' => str_pad("tok-$k-$clave", 48, 'x'), 'publicado_en' => $estado === 'publicado' ? now() : null,
            ]);
        }
    }
});

it('el cliente solo ve sus informes publicados', function () {
    comoAppInformes(['interno' => 'false', 'cliente' => $this->c['a']], function ($c) {
        expect($c->table('informes')->count())->toBe(1)
            ->and($c->table('informes')->where('estado', 'borrador')->count())->toBe(0)
            ->and($c->table('informes')->where('token_verificacion', 'like', 'tok-b-%')->count())->toBe(0);
    });
});

it('el cliente no puede crear ni modificar informes', function () {
    comoAppInformes(['interno' => 'false', 'cliente' => $this->c['a']], function ($c) {
        expect($c->table('informes')->update(['estado' => 'borrador']))->toBe(0);
        expect(fn () => $c->table('informes')->insert([
            'orden_trabajo_id' => $c->table('ordenes_trabajo')->value('id'), 'token_verificacion' => str_pad('intruso', 48, 'x'),
        ]))->toThrow(QueryException::class);
    });
});

it('el personal interno ve todos y sin contexto no se ve nada', function () {
    comoAppInformes(['interno' => 'true'], fn ($c) => expect($c->table('informes')->count())->toBe(3));
    comoAppInformes([], fn ($c) => expect($c->table('informes')->count())->toBe(0));
});

it('la verificación pública funciona sin contexto y solo para publicados', function () {
    comoAppInformes([], function ($c) {
        expect($c->select('select * from app_verificar_informe(?)', [str_pad('tok-a-pub', 48, 'x')]))->toHaveCount(1)
            ->and($c->select('select * from app_verificar_informe(?)', [str_pad('tok-a-borr', 48, 'x')]))->toHaveCount(0)
            ->and($c->select('select * from app_verificar_informe(?)', ['inexistente']))->toHaveCount(0);
    });
});
