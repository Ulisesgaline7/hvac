<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private function hijas(): array
    {
        return [
            'garantias' => "exists (select 1 from ordenes_trabajo ot where ot.id = garantias.orden_trabajo_id and ot.cliente_id = app_cliente_id())",
            'actas_conformidad' => "exists (select 1 from ordenes_trabajo ot where ot.id = actas_conformidad.orden_trabajo_id and ot.cliente_id = app_cliente_id())",
            'orden_trabajo_equipo' => "exists (select 1 from ordenes_trabajo ot where ot.id = orden_trabajo_equipo.orden_trabajo_id and ot.cliente_id = app_cliente_id())",
            'planes_preventivos' => "exists (select 1 from contratos c where c.id = planes_preventivos.contrato_id and c.cliente_id = app_cliente_id())",
            'contrato_equipo' => "exists (select 1 from contratos c where c.id = contrato_equipo.contrato_id and c.cliente_id = app_cliente_id())",
        ];
    }

    private function asegurarRls(string $tabla): void
    {
        DB::statement("ALTER TABLE {$tabla} ENABLE ROW LEVEL SECURITY");
        $p = DB::selectOne("select 1 as ok from pg_policies where tablename = ? and policyname = 'interno_total'", [$tabla]);
        if (! $p) {
            DB::statement("CREATE POLICY interno_total ON {$tabla} USING (app_es_interno()) WITH CHECK (app_es_interno())");
        }
    }

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        if (! Schema::hasTable('solicitudes_cliente')) {
            Schema::create('solicitudes_cliente', function (Blueprint $t) {
                $t->id();
                $t->foreignId('cliente_id')->constrained('clientes');
                $t->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
                $t->string('tipo', 20);
                $t->foreignId('orden_trabajo_id')->nullable()->constrained('ordenes_trabajo')->nullOnDelete();
                $t->foreignId('garantia_id')->nullable()->constrained('garantias')->nullOnDelete();
                $t->foreignId('equipo_id')->nullable()->constrained('equipos')->nullOnDelete();
                $t->text('descripcion');
                $t->string('estado', 20)->default('nueva');
                $t->text('respuesta')->nullable();
                $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamps();
                $t->index(['cliente_id', 'estado']);
            });
            DB::statement("ALTER TABLE solicitudes_cliente ADD CONSTRAINT solicitudes_cliente_tipo_check CHECK (tipo IN ('servicio','reclamo','garantia','otro'))");
            DB::statement("ALTER TABLE solicitudes_cliente ADD CONSTRAINT solicitudes_cliente_estado_check CHECK (estado IN ('nueva','en_revision','atendida','rechazada'))");
        }

        $rol = config('database.connections.pgsql_app.username') ?: 'usuario_app';
        $existe = DB::selectOne('select 1 as ok from pg_roles where rolname = ?', [$rol]);

        foreach ($this->hijas() as $tabla => $using) {
            if (! Schema::hasTable($tabla)) {
                continue;
            }
            $this->asegurarRls($tabla);
            DB::statement("DROP POLICY IF EXISTS cliente_lectura ON {$tabla}");
            DB::statement("CREATE POLICY cliente_lectura ON {$tabla} FOR SELECT USING ({$using})");
            if ($existe) {
                DB::statement("GRANT SELECT ON {$tabla} TO \"{$rol}\"");
            }
        }

        $this->asegurarRls('solicitudes_cliente');
        DB::statement('DROP POLICY IF EXISTS cliente_lectura ON solicitudes_cliente');
        DB::statement('DROP POLICY IF EXISTS cliente_crea ON solicitudes_cliente');
        DB::statement('CREATE POLICY cliente_lectura ON solicitudes_cliente FOR SELECT USING (cliente_id = app_cliente_id())');
        DB::statement("CREATE POLICY cliente_crea ON solicitudes_cliente FOR INSERT WITH CHECK (
            cliente_id = app_cliente_id()
            AND estado = 'nueva'
            AND respuesta IS NULL
            AND (orden_trabajo_id IS NULL OR EXISTS (select 1 from ordenes_trabajo ot where ot.id = solicitudes_cliente.orden_trabajo_id and ot.cliente_id = app_cliente_id()))
        )");
        if ($existe) {
            DB::statement("GRANT SELECT, INSERT, UPDATE, DELETE ON solicitudes_cliente TO \"{$rol}\"");
            DB::statement("GRANT USAGE, SELECT ON SEQUENCE solicitudes_cliente_id_seq TO \"{$rol}\"");
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->hijas()) as $tabla) {
            if (Schema::hasTable($tabla)) {
                DB::statement("DROP POLICY IF EXISTS cliente_lectura ON {$tabla}");
            }
        }
        Schema::dropIfExists('solicitudes_cliente');
    }
};
