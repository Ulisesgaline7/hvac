<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoEquipo;
use App\Models\Sucursal;
use App\Models\TipoEquipo;
use Dompdf\Dompdf;

class InformeOtService
{
    public function pdf(OrdenTrabajo $ot): string
    {
        $dompdf = new Dompdf(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false]);
        $dompdf->loadHtml(view('pdf.informe-ot', $this->datos($ot))->render());
        $dompdf->setPaper('letter');
        $dompdf->render();

        return $dompdf->output();
    }

    public function datos(OrdenTrabajo $ot): array
    {
        $lineas = OrdenTrabajoEquipo::where('orden_trabajo_id', $ot->id)
            ->with(['equipo', 'ficha.version'])
            ->orderBy('id')
            ->get()
            ->map(function ($l) {
                $ficha = $l->ficha;
                $tipoId = $l->equipo?->tipo_equipo_id ?? $l->tipo_equipo_id;

                return [
                    'codigo' => $l->equipo?->codigo ?? '—',
                    'tipo' => $tipoId ? TipoEquipo::find($tipoId)?->nombre : null,
                    'estado_resultante' => $l->estado_resultante,
                    'observaciones' => $l->observaciones,
                    'tiene_ficha' => (bool) $ficha,
                    'completa' => (bool) $ficha?->completa,
                    'alertas' => $ficha?->alertas ?? [],
                    'plantilla' => $ficha?->version?->plantilla?->nombre ?? null,
                    'filas' => $ficha ? $this->filas($ficha->version->definicion ?? [], $ficha->respuestas ?? []) : [],
                ];
            });

        return [
            'ot' => $ot,
            'cliente' => Cliente::find($ot->cliente_id),
            'sucursal' => Sucursal::find($ot->sucursal_id),
            'tecnico' => $ot->tecnico?->user?->name,
            'lineas' => $lineas,
            'generado' => now()->format('d/m/Y H:i'),
        ];
    }

    /** Una fila por campo de la plantilla (omite foto y firma, que llegan con el módulo de evidencias). */
    private function filas(array $campos, array $resp): array
    {
        $filas = [];

        foreach ($campos as $c) {
            if (in_array($c['tipo'], FormularioDinamico::PENDIENTES, true)) {
                continue;
            }

            $v = $resp[$c['clave']] ?? null;
            $fuera = false;

            if ($c['tipo'] === 'numero' && is_numeric($v)) {
                $min = $c['min'] ?? null;
                $max = $c['max'] ?? null;
                $fuera = ($min !== null && $min !== '' && $v < $min) || ($max !== null && $max !== '' && $v > $max);
            }

            $texto = match (true) {
                $v === null || $v === '' || $v === [] => '—',
                $c['tipo'] === 'casilla' => $v ? 'Sí' : 'No',
                is_array($v) => implode(', ', $v),
                $c['tipo'] === 'numero' => $v . (! empty($c['unidad']) ? ' ' . $c['unidad'] : ''),
                default => (string) $v,
            };

            $filas[] = ['etiqueta' => $c['etiqueta'], 'valor' => $texto, 'fuera' => $fuera, 'nota' => $c['tipo'] === 'nota'];
        }

        return $filas;
    }
}
