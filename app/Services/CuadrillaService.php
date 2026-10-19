<?php

namespace App\Services;

use App\Enums\EstadoOt;
use App\Enums\TipoOt;
use App\Models\OrdenTrabajo;
use App\Models\Tecnico;
use DomainException;
use Illuminate\Support\Facades\DB;

/** Cuadrilla de una OT: un líder (es el tecnico_id de la OT) y los demás miembros con su rol. */
class CuadrillaService
{
    private const ROLES_MIEMBRO = ['tecnico', 'ayudante', 'supervisor'];

    /**
     * @param  array<int,string>  $miembros  tecnico_id => rol (tecnico, ayudante o supervisor); el líder se indica aparte
     */
    public function asignar(OrdenTrabajo $ot, int $liderId, array $miembros = []): OrdenTrabajo
    {
        $miembros = collect($miembros)->except($liderId);

        foreach ($miembros as $rol) {
            if (! in_array($rol, self::ROLES_MIEMBRO, true)) {
                throw new DomainException("Rol de cuadrilla no válido: {$rol}.");
            }
        }

        $ids = $miembros->keys()->prepend($liderId)->map(fn ($i) => (int) $i)->all();
        $tecnicos = Tecnico::whereIn('id', $ids)->get();

        if ($tecnicos->count() !== count($ids)) {
            throw new DomainException('Hay técnicos de la cuadrilla que no existen.');
        }
        if ($tecnicos->contains(fn ($t) => $t->estado_homologacion !== 'aprobado')) {
            throw new DomainException('Solo se pueden asignar técnicos aprobados.');
        }
        if ($ot->tipo === TipoOt::Instalacion && count($ids) < 2) {
            throw new DomainException('Una instalación necesita una cuadrilla de al menos 2 personas.');
        }

        DB::transaction(function () use ($ot, $liderId, $miembros) {
            DB::table('orden_trabajo_tecnico')->where('orden_trabajo_id', $ot->id)->delete();

            $ahora = now();
            $filas = [['orden_trabajo_id' => $ot->id, 'tecnico_id' => $liderId, 'rol' => 'lider', 'created_at' => $ahora, 'updated_at' => $ahora]];
            foreach ($miembros as $tecnicoId => $rol) {
                $filas[] = ['orden_trabajo_id' => $ot->id, 'tecnico_id' => (int) $tecnicoId, 'rol' => $rol, 'created_at' => $ahora, 'updated_at' => $ahora];
            }
            DB::table('orden_trabajo_tecnico')->insert($filas);

            $ot->tecnico_id = $liderId;

            if ($ot->estado === EstadoOt::Pendiente) {
                $ot->cambiarEstado(EstadoOt::Asignada, 'Asignación de cuadrilla');
            } else {
                $ot->save();
            }
        });

        return $ot->fresh();
    }

    /** Composición sugerida por el tarifario (sección 8). */
    public function composicionSugerida(int $tecnicos, bool $trabajoEnAltura = false): array
    {
        return [
            'tecnicos' => $tecnicos,
            'ayudantes' => $trabajoEnAltura ? max(1, intdiv($tecnicos, 2)) : 0,
            'supervisor' => $tecnicos >= 4,
        ];
    }
}
