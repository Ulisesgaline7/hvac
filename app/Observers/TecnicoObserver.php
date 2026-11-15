<?php

namespace App\Observers;

use App\Models\Colaborador;
use App\Models\Tecnico;

class TecnicoObserver
{
    public function created(Tecnico $tecnico): void
    {
        $colaborador = Colaborador::withTrashed()->firstOrCreate(
            ['user_id' => $tecnico->user_id],
            [
                'nombre' => $tecnico->user?->name ?? 'Técnico',
                'rtn' => $tecnico->rtn,
                'telefono' => $tecnico->telefono,
                'estado' => 'activo',
            ],
        );

        if (! $colaborador->trashed() && ! $colaborador->vinculoActivo()->exists()) {
            $colaborador->cambiarVinculo('independiente', 'Técnico', now());
        }
    }
}
