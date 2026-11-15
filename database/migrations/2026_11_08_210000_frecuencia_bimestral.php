<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private function cambiar(array $valores): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        $lista = "'" . implode("','", $valores) . "'";
        foreach (['planes_preventivos', 'contrato_equipo'] as $tabla) {
            $cs = DB::select("select conname from pg_constraint where conrelid = ?::regclass and contype = 'c' and pg_get_constraintdef(oid) ilike '%frecuencia%'", [$tabla]);
            foreach ($cs as $c) {
                DB::statement("ALTER TABLE {$tabla} DROP CONSTRAINT \"{$c->conname}\"");
                DB::statement("ALTER TABLE {$tabla} ADD CONSTRAINT \"{$c->conname}\" CHECK (frecuencia IS NULL OR frecuencia IN ({$lista}))");
            }
        }
    }

    public function up(): void
    {
        $this->cambiar(['mensual', 'bimestral', 'trimestral', 'semestral', 'anual', 'unica']);
    }

    public function down(): void
    {
        $this->cambiar(['mensual', 'trimestral', 'semestral', 'anual', 'unica']);
    }
};
