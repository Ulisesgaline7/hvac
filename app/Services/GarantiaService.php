<?php

namespace App\Services;

use App\Enums\TipoOt;
use App\Models\Equipo;
use App\Models\Garantia;
use App\Models\OrdenTrabajo;
use Illuminate\Support\Collection;

class GarantiaService
{
    /** Categoría de garantía según el tipo de OT y la familia del equipo. */
    public function categoriaPara(OrdenTrabajo $ot, ?Equipo $equipo): string
    {
        $tipo = $ot->tipo instanceof TipoOt ? $ot->tipo : TipoOt::from((string) $ot->tipo);
        $familia = $equipo?->tipo?->familia;

        return match (true) {
            $tipo === TipoOt::Instalacion => 'instalacion',
            in_array($tipo, [TipoOt::Correctivo, TipoOt::Emergencia], true) => 'correctivo',
            in_array($familia, ['cuartos_frios', 'refrigeracion_comercial'], true) => 'refrigeracion_comercial',
            default => 'preventivo',
        };
    }

    /** Emite una garantía por cada equipo de la OT. Es idempotente: no duplica. */
    public function emitir(OrdenTrabajo $ot): Collection
    {
        if ($ot->tipo === TipoOt::VisitaTecnica) {
            return collect();
        }

        $desde = ($ot->cerrada_en ?? now())->copy()->startOfDay();

        return $ot->lineas()->with('equipo.tipo')->get()->map(function ($linea) use ($ot, $desde) {
            $existente = Garantia::where('orden_trabajo_equipo_id', $linea->id)->first();
            if ($existente) {
                return $existente;
            }

            $categoria = $this->categoriaPara($ot, $linea->equipo);
            $cfg = config("tarifario.categorias.{$categoria}");

            return Garantia::create([
                'orden_trabajo_id' => $ot->id,
                'orden_trabajo_equipo_id' => $linea->id,
                'equipo_id' => $linea->equipo_id,
                'categoria' => $categoria,
                'dias' => $cfg['dias'],
                'desde' => $desde->toDateString(),
                'hasta' => $desde->copy()->addDays($cfg['dias'])->toDateString(),
                'cubre' => $cfg['cubre'],
                'no_cubre' => $cfg['no_cubre'],
                'quien_corrige' => $cfg['corrige'],
            ]);
        });
    }
}
