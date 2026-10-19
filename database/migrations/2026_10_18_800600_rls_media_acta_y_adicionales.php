<?php

use App\Models\ActaConformidad;
use App\Models\OrdenTrabajoEquipo;
use App\Models\TrabajoAdicional;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Condición de visibilidad: el archivo pertenece a una fila que el usuario ya puede ver (hereda su RLS). */
    private function visible(array $tipos): string
    {
        $partes = [];
        foreach ($tipos as $clase => $tabla) {
            $partes[] = "(model_type = '" . $clase . "' AND EXISTS (SELECT 1 FROM {$tabla} o WHERE o.id = media.model_id))";
        }

        return '(' . implode(' OR ', $partes) . ')';
    }

    private function recrear(array $tipos): void
    {
        $visible = $this->visible($tipos);
        $noCliente = "coalesce(current_setting('app.cliente_id', true), '') = ''";

        DB::unprepared(<<<SQL
DROP POLICY IF EXISTS media_actualiza ON media;
DROP POLICY IF EXISTS media_alta ON media;
DROP POLICY IF EXISTS media_lectura ON media;

CREATE POLICY media_lectura ON media FOR SELECT USING ({$visible});
CREATE POLICY media_alta ON media FOR INSERT WITH CHECK ({$noCliente} AND {$visible});
CREATE POLICY media_actualiza ON media FOR UPDATE USING ({$noCliente} AND {$visible}) WITH CHECK ({$noCliente} AND {$visible});
SQL);
    }

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $this->recrear([
            OrdenTrabajoEquipo::class => 'orden_trabajo_equipo',
            ActaConformidad::class => 'actas_conformidad',
            TrabajoAdicional::class => 'trabajos_adicionales',
        ]);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $this->recrear([OrdenTrabajoEquipo::class => 'orden_trabajo_equipo']);
    }
};
