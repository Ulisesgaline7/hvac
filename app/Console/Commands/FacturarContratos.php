<?php

namespace App\Console\Commands;

use App\Models\Contrato;
use App\Models\Factura;
use App\Services\FacturacionContratos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FacturarContratos extends Command
{
    protected $signature = 'cobros:facturar-contratos';
    protected $description = 'Genera la factura del periodo vigente de los contratos activos con cuota';

    public function handle(FacturacionContratos $svc): int
    {
        $total = 0;
        $errores = 0;

        foreach (Contrato::where('estado', 'activo')->where('cuota_monto', '>', 0)->cursor() as $c) {
            try {
                $antes = Factura::where('contrato_id', $c->id)->count();
                $svc->facturar($c);
                $total += Factura::where('contrato_id', $c->id)->count() - $antes;
            } catch (\Throwable $e) {
                $errores++;
                Log::error("Facturación de contrato {$c->id} falló: " . $e->getMessage());
                $this->error("Contrato {$c->id}: " . $e->getMessage());
            }
        }

        $this->info("Facturas de contrato creadas: $total" . ($errores ? " · con error: $errores" : ''));

        return $errores ? self::FAILURE : self::SUCCESS;
    }
}
