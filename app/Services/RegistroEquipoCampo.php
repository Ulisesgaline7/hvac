<?php

namespace App\Services;

use App\Enums\EstadoOt;
use App\Models\Area;
use App\Models\Equipo;
use App\Models\OrdenTrabajo;
use App\Models\Tecnico;
use App\Models\User;
use App\Providers\RlsServiceProvider;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/** Alta de un equipo en campo: lo crea (con código y QR) y lo agrega a la OT. */
class RegistroEquipoCampo
{
    private const ESTADOS_OT = [EstadoOt::Asignada, EstadoOt::EnProceso, EstadoOt::EnEspera];

    private const CAMPOS = [
        'area_id', 'tipo_equipo_id', 'marca_id', 'modelo', 'serie', 'capacidad_valor',
        'capacidad_unidad', 'refrigerante_id', 'voltaje', 'corriente',
    ];

    public function registrar(OrdenTrabajo $ot, User $usuario, array $datos): Equipo
    {
        $this->autorizar($ot, $usuario);

        if (! in_array($ot->estado, self::ESTADOS_OT, true)) {
            throw new DomainException('Solo se pueden agregar equipos a una OT asignada, en proceso o en espera.');
        }

        // La autorización ya se comprobó arriba con los permisos del propio usuario.
        return RlsServiceProvider::comoInterno(function () use ($ot, $usuario, $datos) {
            $area = Area::where('id', $datos['area_id'] ?? 0)->where('sucursal_id', $ot->sucursal_id)->first()
                ?? throw new DomainException('El área no pertenece a la sucursal de la OT.');

            return DB::transaction(function () use ($ot, $usuario, $datos, $area) {
                $equipo = Equipo::create(Arr::only($datos, self::CAMPOS) + ['area_id' => $area->id]);

                $ot->lineas()->create([
                    'equipo_id' => $equipo->id,
                    'tipo_equipo_id' => $equipo->tipo_equipo_id,
                    'area_id' => $equipo->area_id,
                    'creado_por' => $usuario->id,
                ]);

                return $equipo;
            });
        });
    }

    private function autorizar(OrdenTrabajo $ot, User $usuario): void
    {
        if ($usuario->hasAnyRole(['administrador', 'coordinador'])) {
            return;
        }

        $tecnicoId = $usuario->hasRole('tecnico') ? Tecnico::where('user_id', $usuario->id)->value('id') : null;

        $miembro = $tecnicoId && DB::table('orden_trabajo_tecnico')
            ->where('orden_trabajo_id', $ot->id)->where('tecnico_id', $tecnicoId)->exists();

        if (! $miembro) {
            throw new DomainException('Solo la cuadrilla de la OT puede agregarle equipos.');
        }
    }
}
