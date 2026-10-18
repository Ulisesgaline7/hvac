<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait AislaPorCliente
{
    abstract protected static function restringirACliente(Builder $consulta, int $clienteId): void;

    public static function bootAislaPorCliente(): void
    {
        static::addGlobalScope('cliente', function (Builder $consulta) {
            $usuario = Auth::user();

            if ($usuario && $usuario->cliente_id) {
                static::restringirACliente($consulta, (int) $usuario->cliente_id);
            }
        });
    }
}
