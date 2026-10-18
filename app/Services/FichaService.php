<?php

namespace App\Services;

use App\Enums\TipoOt;
use App\Models\FichaTecnica;
use App\Models\OrdenTrabajoEquipo;
use App\Models\PlantillaFormulario;
use App\Models\TipoEquipo;
use DomainException;

class FichaService
{
    public function obtenerOCrear(OrdenTrabajoEquipo $linea): FichaTecnica
    {
        if ($linea->ficha) {
            return $linea->ficha;
        }

        $version = $this->plantillaPara($linea)->ultimaVersion
            ?? throw new DomainException('La plantilla no tiene versiones publicadas.');

        return FichaTecnica::create([
            'orden_trabajo_id' => $linea->orden_trabajo_id,
            'orden_trabajo_equipo_id' => $linea->id,
            'plantilla_version_id' => $version->id,
            'respuestas' => [],
        ]);
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

        $tipoId = $linea->equipo?->tipo_equipo_id ?? $linea->tipo_equipo_id;
        $plantillaId = $tipoId ? TipoEquipo::find($tipoId)?->plantilla_id : null;

        return ($plantillaId ? PlantillaFormulario::find($plantillaId) : null)
            ?? throw new DomainException('El tipo de equipo no tiene plantilla de formulario asignada.');
    }
}
