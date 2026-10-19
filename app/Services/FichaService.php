<?php

namespace App\Services;

use App\Enums\TipoOt;
use App\Models\FichaTecnica;
use App\Models\OrdenTrabajoEquipo;
use App\Models\PlantillaFormulario;
use App\Models\TipoEquipo;
use DomainException;
use Illuminate\Support\Facades\Storage;

class FichaService
{
    public function obtenerOCrear(OrdenTrabajoEquipo $linea): FichaTecnica
    {
        $existente = FichaTecnica::where('orden_trabajo_equipo_id', $linea->id)->first();

        if ($existente) {
            $linea->setRelation('ficha', $existente);

            return $existente;
        }

        $version = $this->plantillaPara($linea)->ultimaVersion
            ?? throw new DomainException('La plantilla no tiene versiones publicadas.');

        $ficha = FichaTecnica::create([
            'orden_trabajo_id' => $linea->orden_trabajo_id,
            'orden_trabajo_equipo_id' => $linea->id,
            'plantilla_version_id' => $version->id,
            'respuestas' => [],
        ]);

        $linea->setRelation('ficha', $ficha);

        return $ficha;
    }

    /** Guarda respuestas (borrador permitido) y recalcula completitud y alertas. */
    public function guardar(FichaTecnica $ficha, array $respuestas): FichaTecnica
    {
        $campos = $ficha->version->definicion;

        $ficha->update([
            'respuestas' => $respuestas,
            'completa' => FormularioDinamico::faltantes($campos, $respuestas) === [],
            'alertas' => FormularioDinamico::fueraDeRango($campos, $respuestas),
        ]);

        return $ficha;
    }

    private function plantillaPara(OrdenTrabajoEquipo $linea): PlantillaFormulario
    {
        $ot = $linea->ordenTrabajo;

        if ($ot->tipo === TipoOt::Instalacion) {
            return PlantillaFormulario::where('familia', 'instalacion')->first()
                ?? throw new DomainException('No existe la plantilla de instalación.');
        }

        // Correctivo, emergencia y diagnóstico tienen su propia ficha; si no existe, se usa la del tipo de equipo.
        $tipoTrabajo = match ($ot->tipo) {
            TipoOt::Correctivo, TipoOt::Emergencia => 'correctivo',
            TipoOt::VisitaTecnica => 'diagnostico',
            default => null,
        };
        if ($tipoTrabajo) {
            $propia = PlantillaFormulario::where('tipo_trabajo', $tipoTrabajo)->first();
            if ($propia && $propia->ultimaVersion) {
                return $propia;
            }
        }
        $tipoId = $linea->equipo?->tipo_equipo_id ?? $linea->tipo_equipo_id;
        $plantillaId = $tipoId ? TipoEquipo::find($tipoId)?->plantilla_id : null;

        return ($plantillaId ? PlantillaFormulario::find($plantillaId) : null)
            ?? throw new DomainException('El tipo de equipo no tiene plantilla de formulario asignada.');
    }

    /** Guarda firmas (PNG base64) y fotos (rutas temporales en el disco local) como media de la línea. */
    public function adjuntar(OrdenTrabajoEquipo $linea, array $data): void
    {
        foreach ($data as $k => $v) {
            if (! str_starts_with($k, 'f_') || empty($v)) {
                continue;
            }

            $coleccion = substr($k, 2);

            if (is_string($v) && str_starts_with($v, 'data:image/png;base64,')) {
                if (strlen($v) > 1_400_000) {
                    throw new DomainException('La firma es demasiado grande.');
                }

                $linea->clearMediaCollection($coleccion);
                $linea->addMediaFromBase64($v, ['image/png'])->usingFileName($coleccion . '.png')->toMediaCollection($coleccion, 'local');

                continue;
            }

            foreach (array_values((array) $v) as $ruta) {
                $linea->addMedia(Storage::disk('local')->path($ruta))->toMediaCollection($coleccion, 'local');
                Storage::disk('local')->delete($ruta);
            }
        }
    }

    /**
     * Recalcula completitud y alertas desde la realidad: los marcadores de foto y firma
     * se reconstruyen según los archivos que existen hoy (si se borró uno, la ficha deja de estar completa).
     */
    public function recalcular(FichaTecnica $ficha): FichaTecnica
    {
        $linea = $ficha->linea;
        $linea->unsetRelation('media');
        $respuestas = $ficha->respuestas ?? [];

        foreach ($ficha->version->definicion as $c) {
            if (! in_array($c['tipo'], ['foto', 'firma'], true)) {
                continue;
            }

            unset($respuestas[$c['clave']]);

            if ($linea->getMedia($c['clave'])->isNotEmpty()) {
                $respuestas[$c['clave']] = 'adjunto';
            }
        }

        return $this->guardar($ficha, $respuestas);
    }
}
