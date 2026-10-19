<?php

namespace App\Console\Commands;

use App\Models\Contrato;
use App\Services\FacturacionContratos;
use Illuminate\Console\Command;

class FacturarContratos extends Command
{
    protected $signature = 'cobros:facturar-contratos';
    protected $description = 'Genera la factura del periodo vigente de los contratos activos con cuota';

    public function handle(FacturacionContratos $svc): int
    {
        $total = 0;
        Contrato::where('estado', 'activo')->where('cuota_monto', '>', 0)->each(function ($c) use ($svc, &$total) {
            $antes = \App\Models\Factura::where('contrato_id', $c->id)->count();
            $svc->facturar($c);
            $total += \App\Models\Factura::where('contrato_id', $c->id)->count() - $antes;
        });
        $this->info("Facturas de contrato creadas: $total");

        return self::SUCCESS;
    }
}
