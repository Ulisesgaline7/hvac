<?php

namespace App\Console\Commands;

use App\Services\FinanzasResumen;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FinanzasCommand extends Command
{
    protected $signature = 'cobros:resumen {--desde= : AAAA-MM-DD (por defecto, inicio del mes)} {--hasta= : AAAA-MM-DD (por defecto, hoy)}';
    protected $description = 'Solo lectura: resumen financiero (facturado, cobrado, por cobrar, antigüedad, pagos a técnicos)';

    public function handle(FinanzasResumen $svc): int
    {
        $desde = $this->option('desde') ? Carbon::parse($this->option('desde')) : now()->startOfMonth();
        $hasta = $this->option('hasta') ? Carbon::parse($this->option('hasta')) : now();
        $d = $svc->datos($desde, $hasta);
        $l = fn ($v) => 'L ' . number_format($v, 2);

        $this->info("Periodo {$d['rango'][0]} a {$d['rango'][1]}");
        $this->table(['Concepto', 'Monto'], [
            ["Facturado ({$d['facturado']['n']} facturas, sin ISV)", $l($d['facturado']['subtotal'])],
            ['ISV', $l($d['facturado']['isv'])],
            ['Facturado total', $l($d['facturado']['total'])],
            ['  de contratos', $l($d['facturado']['contrato'])],
            ['Cobrado (pagos)', $l($d['cobrado']['pagos'])],
            ['Cobrado (anticipos)', $l($d['cobrado']['anticipos'])],
            ['Anticipos aún sin factura (todo el tiempo)', $l($d['anticipos_sin_factura'])],
        ]);

        $this->info("Por cobrar: {$l($d['por_cobrar']['total'])} en {$d['por_cobrar']['facturas']} facturas");
        $this->table(['Antigüedad', 'Saldo'], collect($d['por_cobrar']['antiguedad'])->map(fn ($v, $k) => [$k, $l($v)])->values()->all());
        $this->table(['Cliente (top 10)', 'Saldo'], collect($d['por_cobrar']['top_clientes'])->map(fn ($v, $k) => [$k, $l($v)])->values()->all());
        $this->table(['Pagos a técnicos', 'Líneas', 'Monto'], collect($d['tecnicos'])->map(fn ($t, $k) => [$k, $t['n'], $l($t['monto'])])->values()->all());

        return self::SUCCESS;
    }
}
