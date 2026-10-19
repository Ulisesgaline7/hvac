<?php

use App\Support\TarifarioCatalogo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios_catalogo', function (Blueprint $t) {
            $t->id();
            $t->string('codigo')->unique();
            $t->string('categoria');
            $t->string('nombre');
            $t->string('detalle')->nullable();
            $t->decimal('precio', 12, 2);
            $t->decimal('precio_hasta', 12, 2)->nullable();
            $t->boolean('cuenta_equipo')->default(false);
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });

        Schema::create('cotizaciones', function (Blueprint $t) {
            $t->id();
            $t->string('numero')->unique();
            $t->string('estado')->default('borrador');
            $t->foreignId('cliente_id')->constrained('clientes');
            $t->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $t->date('fecha');
            $t->date('valida_hasta')->nullable();
            $t->foreignId('vendedor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->jsonb('recargos')->nullable();
            $t->string('descuento_contrato')->nullable();
            $t->string('descuento_codigo')->nullable();
            $t->decimal('descuento_pct', 6, 4)->default(0);
            $t->decimal('subtotal_servicios', 12, 2)->default(0);
            $t->decimal('ajuste_minimo', 12, 2)->default(0);
            $t->decimal('recargos_monto', 12, 2)->default(0);
            $t->decimal('descuento_monto', 12, 2)->default(0);
            $t->decimal('repuestos_monto', 12, 2)->default(0);
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->decimal('isv', 12, 2)->default(0);
            $t->decimal('total', 12, 2)->default(0);
            $t->text('condiciones')->nullable();
            $t->text('notas')->nullable();
            $t->timestamp('enviada_en')->nullable();
            $t->timestamp('aprobada_en')->nullable();
            $t->string('aprobada_por_nombre')->nullable();
            $t->unsignedBigInteger('contrato_id')->nullable();
            $t->timestamps();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->softDeletes();
            $t->index(['cliente_id', 'estado']);
        });

        Schema::create('cotizacion_lineas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $t->foreignId('servicio_id')->nullable()->constrained('servicios_catalogo')->nullOnDelete();
            $t->string('tipo')->default('servicio');
            $t->string('codigo')->nullable();
            $t->string('descripcion');
            $t->decimal('cantidad', 10, 2)->default(1);
            $t->string('unidad')->nullable();
            $t->decimal('precio_unitario', 12, 2)->default(0);
            $t->decimal('importe', 12, 2)->default(0);
            $t->boolean('cuenta_equipo')->default(false);
            $t->timestamps();
            $t->index('cotizacion_id');
        });

        Schema::create('garantias', function (Blueprint $t) {
            $t->id();
            $t->string('numero')->unique();
            $t->foreignId('orden_trabajo_id')->constrained('ordenes_trabajo')->cascadeOnDelete();
            $t->foreignId('orden_trabajo_equipo_id')->nullable()->unique()->constrained('orden_trabajo_equipo')->nullOnDelete();
            $t->foreignId('equipo_id')->nullable()->constrained('equipos')->nullOnDelete();
            $t->string('categoria');
            $t->unsignedSmallInteger('dias');
            $t->date('desde');
            $t->date('hasta');
            $t->text('cubre')->nullable();
            $t->text('no_cubre')->nullable();
            $t->text('quien_corrige')->nullable();
            $t->string('estado')->default('vigente');
            $t->timestamps();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->index('orden_trabajo_id');
        });

        $ahora = now();
        foreach (TarifarioCatalogo::filas() as $fila) {
            DB::table('servicios_catalogo')->insert($fila + ['created_at' => $ahora, 'updated_at' => $ahora]);
        }

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
ALTER TABLE cotizaciones ADD CONSTRAINT cot_estado_check CHECK (estado IN ('borrador','enviada','aprobada','rechazada','vencida'));
ALTER TABLE cotizacion_lineas ADD CONSTRAINT cotl_tipo_check CHECK (tipo IN ('servicio','repuesto'));
ALTER TABLE garantias ADD CONSTRAINT gar_estado_check CHECK (estado IN ('vigente','vencida','anulada'));

ALTER TABLE servicios_catalogo ENABLE ROW LEVEL SECURITY;
ALTER TABLE cotizaciones ENABLE ROW LEVEL SECURITY;
ALTER TABLE cotizacion_lineas ENABLE ROW LEVEL SECURITY;
ALTER TABLE garantias ENABLE ROW LEVEL SECURITY;

CREATE POLICY interno_total ON servicios_catalogo USING (app_es_interno()) WITH CHECK (app_es_interno());

CREATE POLICY interno_total ON cotizaciones USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY cliente_lectura ON cotizaciones FOR SELECT
    USING (cliente_id = app_cliente_id() AND estado IN ('enviada','aprobada'));

CREATE POLICY interno_total ON cotizacion_lineas USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY cliente_lectura ON cotizacion_lineas FOR SELECT
    USING (cotizacion_id IN (SELECT id FROM cotizaciones WHERE cliente_id = app_cliente_id() AND estado IN ('enviada','aprobada')));

CREATE POLICY interno_total ON garantias USING (app_es_interno()) WITH CHECK (app_es_interno());
CREATE POLICY cliente_lectura ON garantias FOR SELECT
    USING (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE cliente_id = app_cliente_id() AND estado = 'cerrada'));
CREATE POLICY tecnico_lectura ON garantias FOR SELECT
    USING (orden_trabajo_id IN (SELECT id FROM ordenes_trabajo WHERE tecnico_id = app_tecnico_id()));

DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'hvac_app') THEN
        GRANT SELECT, INSERT, UPDATE, DELETE ON servicios_catalogo, cotizaciones, cotizacion_lineas, garantias TO hvac_app;
        GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO hvac_app;
    END IF;
END $$;
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('garantias');
        Schema::dropIfExists('cotizacion_lineas');
        Schema::dropIfExists('cotizaciones');
        Schema::dropIfExists('servicios_catalogo');
    }
};
