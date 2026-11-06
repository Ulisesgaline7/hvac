<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE liquidaciones_tecnico DROP CONSTRAINT IF EXISTS liquidaciones_tecnico_tecnico_id_semana_inicio_unique');
        DB::statement('DROP INDEX IF EXISTS liquidaciones_tecnico_tecnico_id_semana_inicio_unique');
        DB::statement("CREATE UNIQUE INDEX liquidaciones_tecnico_semana_activa_unique ON liquidaciones_tecnico (tecnico_id, semana_inicio) WHERE estado <> 'anulada'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS liquidaciones_tecnico_semana_activa_unique');
        DB::statement('ALTER TABLE liquidaciones_tecnico ADD CONSTRAINT liquidaciones_tecnico_tecnico_id_semana_inicio_unique UNIQUE (tecnico_id, semana_inicio)');
    }
};
