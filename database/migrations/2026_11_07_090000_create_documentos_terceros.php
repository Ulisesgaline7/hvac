<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private function enlace(Blueprint $t, string $columna, string $tabla): void
    {
        $t->unsignedBigInteger($columna)->nullable()->index();
        if (Schema::hasTable($tabla)) {
            $t->foreign($columna)->references('id')->on($tabla)->nullOnDelete();
        }
    }

    public function up(): void
    {
        Schema::create('documentos_terceros', function (Blueprint $t) {
            $t->id();
            $t->string('tipo', 40)->index();
            $t->string('titulo');
            $t->string('emisor')->nullable();
            $t->date('emitido_en')->nullable();
            $t->date('vigente_hasta')->nullable()->index();
            $t->date('revisar_en')->nullable()->index();
            $t->text('notas')->nullable();

            $this->enlace($t, 'refrigerante_id', 'refrigerantes');
            $this->enlace($t, 'cilindro_id', 'cilindros_refrigerante');
            $this->enlace($t, 'insumo_id', 'insumos');
            $this->enlace($t, 'equipo_id', 'equipos');
            $this->enlace($t, 'orden_trabajo_id', 'ordenes_trabajo');
            $this->enlace($t, 'movimiento_refrigerante_id', 'movimientos_refrigerante');
            $this->enlace($t, 'proveedor_id', 'proveedores');

            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::table('cilindros_refrigerante', function (Blueprint $t) {
            $t->string('lote')->nullable();
            $t->date('recibido_en')->nullable();
            $this->enlace($t, 'proveedor_id', 'proveedores');
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
ALTER TABLE documentos_terceros ENABLE ROW LEVEL SECURITY;
CREATE POLICY interno_total ON documentos_terceros USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY tecnico_lectura ON documentos_terceros FOR SELECT USING (app_tecnico_id() IS NOT NULL);
DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'hvac_app') THEN
        GRANT SELECT, INSERT, UPDATE, DELETE ON documentos_terceros TO hvac_app;
        GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO hvac_app;
    END IF;
END $$;
SQL);
    }

    public function down(): void
    {
        Schema::table('cilindros_refrigerante', function (Blueprint $t) {
            if (Schema::hasTable('proveedores')) {
                $t->dropForeign(['proveedor_id']);
            }
            $t->dropColumn(['lote', 'recibido_en', 'proveedor_id']);
        });
        Schema::dropIfExists('documentos_terceros');
    }
};
