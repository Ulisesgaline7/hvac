<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_trabajo_tecnico', function (Blueprint $t) {
            $t->id();
            $t->foreignId('orden_trabajo_id')->constrained('ordenes_trabajo')->cascadeOnDelete();
            $t->foreignId('tecnico_id')->constrained('tecnicos');
            $t->string('rol')->default('tecnico');
            $t->timestamps();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->unique(['orden_trabajo_id', 'tecnico_id']);
            $t->index('tecnico_id');
        });

        // Un solo líder por OT.
        DB::statement("CREATE UNIQUE INDEX ott_un_lider ON orden_trabajo_tecnico (orden_trabajo_id) WHERE rol = 'lider'");

        // Las OT que ya tienen técnico pasan a tener cuadrilla de una persona (él, como líder).
        DB::statement("INSERT INTO orden_trabajo_tecnico (orden_trabajo_id, tecnico_id, rol, created_at, updated_at)
            SELECT id, tecnico_id, 'lider', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP FROM ordenes_trabajo WHERE tecnico_id IS NOT NULL AND deleted_at IS NULL");

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
ALTER TABLE orden_trabajo_tecnico ADD CONSTRAINT ott_rol_check CHECK (rol IN ('lider','tecnico','ayudante','supervisor'));
ALTER TABLE orden_trabajo_tecnico ENABLE ROW LEVEL SECURITY;

CREATE POLICY interno_total ON orden_trabajo_tecnico USING (app_es_interno()) WITH CHECK (app_es_interno());
-- Sin subconsultas a ordenes_trabajo: evita recursión cuando esas políticas consulten la cuadrilla (paso B).
CREATE POLICY tecnico_lectura ON orden_trabajo_tecnico FOR SELECT USING (tecnico_id = app_tecnico_id());

DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'hvac_app') THEN
        GRANT SELECT, INSERT, UPDATE, DELETE ON orden_trabajo_tecnico TO hvac_app;
        GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO hvac_app;
    END IF;
END $$;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_trabajo_tecnico');
    }
};
