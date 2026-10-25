<?php

namespace App\Services;

use App\Enums\EstadoOt;
use App\Models\FichaTecnica;
use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoEquipo;
use App\Models\Tecnico;
use App\Models\User;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/** Paquete de trabajo para llevar sin señal y recepción de las fichas llenadas offline. */
class OfflineTecnico
{
    private function estados(): array
    {
        return [EstadoOt::Asignada, EstadoOt::EnProceso, EstadoOt::EnEspera];
    }

    private function tecnicoDe(User $usuario): Tecnico
    {
        return ($usuario->hasRole('tecnico') ? Tecnico::where('user_id', $usuario->id)->first() : null)
            ?? throw new DomainException('Solo los técnicos pueden usar esta función.', 403);
    }

    /** OT activas del técnico, con equipos, fichas y la definición de cada plantilla. */
    public function paquete(User $usuario): array
    {
        $tecnico = $this->tecnicoDe($usuario);

        $roles = DB::table('orden_trabajo_tecnico')->where('tecnico_id', $tecnico->id)->pluck('rol', 'orden_trabajo_id');

        $ordenes = OrdenTrabajo::with(['cliente', 'sucursal', 'lineas.equipo.tipo'])
            ->whereIn('id', $roles->keys())
            ->whereIn('estado', array_map(fn ($e) => $e->value, $this->estados()))
            ->orderBy('fecha_programada')->get();

        $fichas = app(FichaService::class);
        $plantillas = [];

        $salida = $ordenes->map(function (OrdenTrabajo $ot) use ($roles, $fichas, &$plantillas) {
            $rol = $roles[$ot->id];

            return [
                'id' => $ot->id,
                'numero' => $ot->numero,
                'tipo' => $ot->tipo->value,
                'estado' => $ot->estado->value,
                'fecha_programada' => $ot->fecha_programada?->toDateString(),
                'cliente' => $ot->cliente?->razon_social,
                'sucursal' => $ot->sucursal?->nombre,
                'rol' => $rol,
                'editable' => $rol === 'lider',
                'lineas' => $ot->lineas->map(function (OrdenTrabajoEquipo $l) use ($fichas, &$plantillas) {
                    $base = [
                        'id' => $l->id,
                        'equipo_codigo' => $l->equipo?->codigo,
                        'equipo_tipo' => $l->equipo?->tipo?->nombre,
                    ];

                    try {
                        $ficha = $fichas->obtenerOCrear($l);
                    } catch (DomainException $e) {
                        return $base + ['ficha' => null, 'error' => $e->getMessage()];
                    }

                    $plantillas[$ficha->plantilla_version_id] ??= $ficha->version->definicion;

                    return $base + ['ficha' => [
                        'version_id' => $ficha->plantilla_version_id,
                        'respuestas' => (object) ($ficha->respuestas ?? []),
                        'completa' => (bool) $ficha->completa,
                        'alertas' => $ficha->alertas ?? [],
                    ]];
                })->values()->all(),
            ];
        })->values()->all();

        return [
            'generado_en' => now()->toIso8601String(),
            'tecnico_id' => $tecnico->id,
            'plantillas' => (object) $plantillas,
            'ordenes' => $salida,
        ];
    }

    /**
     * Recibe una ficha llenada sin señal. Solo el líder; el servidor recalcula completitud y alertas.
     *
     * @param  array<string,mixed>  $respuestas  clave => valor (sin fotos ni firmas)
     * @param  array<string,string>  $firmas  clave => PNG en base64 (data URI)
     */
    public function guardarFicha(User $usuario, int $lineaId, int $versionId, array $respuestas, array $firmas = []): FichaTecnica
    {
        $tecnico = $this->tecnicoDe($usuario);

        $linea = OrdenTrabajoEquipo::with('ordenTrabajo')->find($lineaId)
            ?? throw new DomainException('No se encontró el equipo de la OT.', 404);
        $ot = $linea->ordenTrabajo;

        $esLider = DB::table('orden_trabajo_tecnico')
            ->where('orden_trabajo_id', $ot->id)->where('tecnico_id', $tecnico->id)->where('rol', 'lider')->exists();
        if (! $esLider) {
            throw new DomainException('Solo el líder de la OT puede enviar la ficha.', 403);
        }

        if (! in_array($ot->estado, $this->estados(), true)) {
            throw new DomainException('La OT ya no admite cambios en la ficha.', 422);
        }

        $servicio = app(FichaService::class);
        $ficha = $servicio->obtenerOCrear($linea);

        if ((int) $ficha->plantilla_version_id !== $versionId) {
            throw new DomainException('La ficha cambió de versión. Descargue sus OT de nuevo.', 409);
        }

        $campos = collect($ficha->version->definicion)->keyBy('clave');
        $editables = $campos->reject(fn ($c) => in_array($c['tipo'], ['foto', 'firma'], true))->keys()->all();

        $merged = $ficha->respuestas ?? [];
        foreach (Arr::only($respuestas, $editables) as $clave => $valor) {
            if ($valor === null || $valor === '' || $valor === []) {
                unset($merged[$clave]);
            } else {
                $merged[$clave] = $valor;
            }
        }

        $adjuntos = [];
        foreach ($firmas as $clave => $png) {
            if (($campos[$clave]['tipo'] ?? null) === 'firma') {
                $adjuntos['f_' . $clave] = $png;
            }
        }

        return DB::transaction(function () use ($servicio, $linea, $ficha, $merged, $adjuntos) {
            $servicio->adjuntar($linea, $adjuntos);

            return $servicio->recalcular($servicio->guardar($ficha, $merged));
        });
    }
}
