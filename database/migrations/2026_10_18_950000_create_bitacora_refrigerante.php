<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cilindros_refrigerante', function (Blueprint $t) {
            $t->id();
            $t->string('codigo')->unique();
            $t->foreignId('refrigerante_id')->constrained('refrigerantes');
            $t->decimal('peso_inicial_kg', 10, 2);
            $t->string('estado')->default('activo');
            $t->text('notas')->nullable();
            $t->timestamps();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->softDeletes();
        });

        Schema::create('movimientos_refrigerante', function (Blueprint $t) {
            $t->id();
            $t->foreignId('orden_trabajo_id')->constrained('ordenes_trabajo')->cascadeOnDelete();
            $t->foreignId('orden_trabajo_equipo_id')->constrained('orden_trabajo_equipo')->cascadeOnDelete();
            $t->foreignId('cilindro_id')->nullable()->constrained('cilindros_refrigerante')->nullOnDelete();
            $t->string('tipo');
            $t->decimal('cantidad_kg', 10, 2);
            $t->timestamp('registrado_en')->useCurrent();
            $t->text('observaciones')->nullable();
            $t->timestamps();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->index('orden_trabajo_id');
            $t->index('cilindro_id');
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
ALTER TABLE cilindros_refrigerante ADD CONSTRAINT cil_peso_check CHECK (peso_inicial_kg > 0);
ALTER TABLE movimientos_refrigerante ADD CONSTRAINT mov_tipo_check CHECK (tipo IN ('carga','recuperacion'));
ALTER TABLE movimientos_refrigerante ADD CONSTRAINT mov_cantidad_check CHECK (cantidad_kg > 0);
ALTER TABLE movimientos_refrigerante ADD CONSTRAINT mov_carga_cilindro_check CHECK (tipo <> 'carga' OR cilindro_id IS NOT NULL);

ALTER TABLE cilindros_refrigerante ENABLE ROW LEVEL SECURITY;
CREATE POLICY interno_total ON cilindros_refrigerante USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY tecnico_lectura ON cilindros_refrigerante FOR SELECT USING (app_tecnico_id() IS NOT NULL);

ALTER TABLE movimientos_refrigerante ENABLE ROW LEVEL SECURITY;
CREATE POLICY interno_total ON movimientos_refrigerante USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY cliente_lectura ON movimientos_refrigerante FOR SELECT
    USING (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE cliente_id = app_cliente_id() AND estado = 'cerrada'));
CREATE POLICY tecnico_lectura ON movimientos_refrigerante FOR SELECT
    USING (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id()));
CREATE POLICY tecnico_alta ON movimientos_refrigerante FOR INSERT
    WITH CHECK (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id()));
CREATE POLICY tecnico_actualiza ON movimientos_refrigerante FOR UPDATE
    USING (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id()))
    WITH CHECK (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id()));
SQL);

        if (DB::table('pg_roles')->where('rolname', 'hvac_app')->exists()) {
            DB::unprepared('GRANT SELECT, INSERT, UPDATE, DELETE ON cilindros_refrigerante, movimientos_refrigerante TO hvac_app; GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO hvac_app;');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_refrigerante');
        Schema::dropIfExists('cilindros_refrigerante');
    }
};
