<?php

namespace App\Console\Commands;

use App\Enums\EstadoContrato;
use App\Models\Contrato;
use App\Services\GeneradorCalendario;
use Illuminate\Console\Command;

class GenerarOtPreventivas extends Command
{
    protected $signature = 'ot:generar-preventivas';

    protected $description = 'Crea las OT preventivas de los contratos activos dentro de su ventana de anticipación';

    public function handle(GeneradorCalendario $generador): int
    {
        $total = 0;

        Contrato::where('estado', EstadoContrato::Activo->value)->each(function (Contrato $contrato) use ($generador, &$total) {
            $total += $generador->generar($contrato);
        });

        $this->info("OT preventivas creadas: $total");

        return self::SUCCESS;
    }
}
