<?php

use App\Models\ActaConformidad;
use App\Models\OrdenTrabajoEquipo;
use App\Models\TrabajoAdicional;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TIPOS = [
        OrdenTrabajoEquipo::class => 'orden_trabajo_equipo',
        ActaConformidad::class => 'actas_conformidad',
        TrabajoAdicional::class => 'trabajos_adicionales',
    ];

    /** Visible si la fila dueña es visible; para un cliente, además la OT debe estar cerrada. */
    private function condicion(bool $clienteSoloCerradas): string
    {
        $noCliente = "coalesce(current_setting('app.cliente_id', true), '') = ''";
        $partes = [];

        foreach (self::TIPOS as $clase => $tabla) {
            $cerrada = $clienteSoloCerradas
                ? " AND ({$noCliente} OR EXISTS (SELECT 1 FROM {$tabla} c JOIN ordenes_trabajo ot ON ot.id = c.orden_trabajo_id WHERE c.id = media.model_id AND ot.estado = 'cerrada'))"
                : '';

            $partes[] = "(model_type = '{$clase}' AND EXISTS (SELECT 1 FROM {$tabla} o WHERE o.id = media.model_id){$cerrada})";
        }

        return '(' . implode(' OR ', $partes) . ')';
    }

    private function aplicar(bool $clienteSoloCerradas): void
    {
        $cond = $this->condicion($clienteSoloCerradas);

        DB::unprepared("DROP POLICY IF EXISTS media_lectura ON media; CREATE POLICY media_lectura ON media FOR SELECT USING ({$cond});");
    }

    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $this->aplicar(true);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $this->aplicar(false);
        }
    }
};
