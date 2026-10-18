<?php

namespace App\Providers;

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class RlsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $usuario = config('rls.usuario_app');

        if ($usuario && ! $this->app->runningInConsole() && config('database.default') === 'pgsql') {
            config([
                'database.connections.pgsql_app' => array_merge(config('database.connections.pgsql'), [
                    'username' => $usuario,
                    'password' => config('rls.password_app'),
                ]),
                'database.default' => 'pgsql_app',
            ]);
        }
    }

    public function boot(): void
    {
        Event::listen(Authenticated::class, function (Authenticated $evento) {
            $conexion = DB::connection();

            if ($conexion->getDriverName() !== 'pgsql') {
                return;
            }

            $usuario = $evento->user;
            $interno = method_exists($usuario, 'hasAnyRole')
                && $usuario->hasAnyRole(['administrador', 'coordinador']);
            $clienteId = (! $interno && ! empty($usuario->cliente_id)) ? (string) $usuario->cliente_id : '';

            $conexion->select(
                "select set_config('app.es_interno', ?, false), set_config('app.cliente_id', ?, false)",
                [$interno ? 'true' : 'false', $clienteId]
            );

            $this->app->terminating(function () {
                DB::connection()->select("select set_config('app.es_interno', '', false), set_config('app.cliente_id', '', false)");
            });
        });
    }
}
