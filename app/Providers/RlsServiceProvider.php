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
        Event::listen(Authenticated::class, fn (Authenticated $e) => self::aplicar($e->user));

        $this->app->terminating(fn () => self::limpiar());
    }

    public static function aplicar($usuario): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $interno = method_exists($usuario, 'hasAnyRole')
            && $usuario->hasAnyRole(['administrador', 'coordinador']);
        $clienteId = (! $interno && ! empty($usuario->cliente_id)) ? (string) $usuario->cliente_id : '';

        self::fijar($interno ? 'true' : 'false', $clienteId, (string) $usuario->getAuthIdentifier());
    }

    public static function limpiar(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            self::fijar('', '', '');
        }
    }

    /** Ejecuta $fn con contexto interno y lo limpia al terminar. Solo para flujos ya autorizados por otro medio (p. ej. un token validado). */
    public static function comoInterno(callable $fn): mixed
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return $fn();
        }

        self::fijar('true', '', '');
        try {
            return $fn();
        } finally {
            self::limpiar();
        }
    }
    private static function fijar(string $interno, string $clienteId, string $userId): void
    {
        DB::connection()->select(
            "select set_config('app.es_interno', ?, false), set_config('app.cliente_id', ?, false), set_config('app.user_id', ?, false)",
            [$interno, $clienteId, $userId]
        );
    }
}
