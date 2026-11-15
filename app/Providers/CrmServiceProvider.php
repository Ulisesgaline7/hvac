<?php

namespace App\Providers;

use App\Models\Cotizacion;
use App\Services\CrmCotizacion;
use Illuminate\Support\ServiceProvider;

class CrmServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Cotizacion::updated(function (Cotizacion $c) {
            try {
                if ($c->wasChanged('estado') && (string) ($c->estado->value ?? $c->estado) === 'aprobada') {
                    CrmCotizacion::marcarGanada($c);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }
}
