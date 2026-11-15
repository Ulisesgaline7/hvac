<?php

namespace App\Observers;

use App\Models\LiquidacionTecnico;
use App\Services\NotificadorTecnico;
use Illuminate\Support\Facades\DB;

class LiquidacionTecnicoObserver
{
    public bool $afterCommit = true;

    public function updated(LiquidacionTecnico $l): void
    {
        if (! $l->wasChanged('estado')) {
            return;
        }

        $clave = match ($l->estado) {
            'pagada' => 'liquidacion_pagada',
            'anulada' => 'liquidacion_anulada',
            default => null,
        };
        if (! $clave) {
            return;
        }

        $t = DB::table('tecnicos')->join('users', 'users.id', '=', 'tecnicos.user_id')
            ->where('tecnicos.id', $l->tecnico_id)->first(['users.id as uid', 'users.name']);

        $m = fn ($v) => 'L ' . number_format((float) $v, 2);

        NotificadorTecnico::enviar($clave, [
            'tecnico' => $t->name ?? '',
            'numero' => (string) $l->numero,
            'semana_inicio' => $l->semana_inicio?->format('d/m/Y') ?? '-',
            'semana_fin' => $l->semana_fin?->format('d/m/Y') ?? '-',
            'bruto' => $m($l->bruto),
            'retencion' => $m($l->retencion),
            'neto' => $m($l->neto),
            'metodo' => (string) ($l->metodo_pago ?? '-'),
            'referencia' => (string) ($l->referencia_pago ?: '-'),
            'fecha_pago' => $l->pagada_en?->format('d/m/Y') ?? '-',
            'motivo' => (string) ($l->motivo_anulacion ?? '-'),
        ], $t->uid ?? null, url('/tecnico/liquidaciones/liquidacions'));
    }
}
