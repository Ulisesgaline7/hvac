<?php

namespace App\Providers;

use App\Models\ReglaTarifario;
use Illuminate\Support\ServiceProvider;
use Throwable;

/** Lo que se edite en el panel (reglas_tarifario) pisa los valores de config/tarifario.php. */
class TarifarioServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        try {
            $guardadas = ReglaTarifario::guardadas();
        } catch (Throwable) {
            return; // tabla aún no migrada: se usa el config tal cual
        }

        if (! $guardadas) {
            return;
        }

        $cfg = config('tarifario', []);
        foreach ($guardadas as $clave => $valor) {
            $cfg[$clave] = ReglaTarifario::paraConfig($clave, $valor);
        }
        config(['tarifario' => $cfg]);
    }
}
