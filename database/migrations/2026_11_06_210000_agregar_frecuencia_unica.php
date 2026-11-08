<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
ALTER TABLE contrato_equipo DROP CONSTRAINT contrato_equipo_frecuencia_check;
ALTER TABLE contrato_equipo ADD CONSTRAINT contrato_equipo_frecuencia_check
    CHECK (frecuencia IS NULL OR frecuencia IN ('mensual','trimestral','semestral','anual','unica'));
ALTER TABLE planes_preventivos DROP CONSTRAINT planes_frecuencia_check;
ALTER TABLE planes_preventivos ADD CONSTRAINT planes_frecuencia_check
    CHECK (frecuencia IN ('mensual','trimestral','semestral','anual','unica'));
SQL);
    }

    // Falla a propósito si ya hay filas con 'unica': no se deben alterar datos para poder revertir.
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
ALTER TABLE contrato_equipo DROP CONSTRAINT contrato_equipo_frecuencia_check;
ALTER TABLE contrato_equipo ADD CONSTRAINT contrato_equipo_frecuencia_check
    CHECK (frecuencia IS NULL OR frecuencia IN ('mensual','trimestral','semestral','anual'));
ALTER TABLE planes_preventivos DROP CONSTRAINT planes_frecuencia_check;
ALTER TABLE planes_preventivos ADD CONSTRAINT planes_frecuencia_check
    CHECK (frecuencia IN ('mensual','trimestral','semestral','anual'));
SQL);
    }
};
