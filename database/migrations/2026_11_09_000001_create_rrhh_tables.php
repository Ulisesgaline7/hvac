<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colaboradores', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $t->string('nombre');
            $t->text('identidad')->nullable();        // cifrada desde el modelo
            $t->string('rtn')->nullable();
            $t->date('fecha_nacimiento')->nullable();
            $t->string('telefono')->nullable();
            $t->string('email_personal')->nullable();
            $t->text('direccion')->nullable();
            $t->string('contacto_emergencia')->nullable();
            $t->string('telefono_emergencia')->nullable();
            $t->string('banco')->nullable();
            $t->text('cuenta_bancaria')->nullable();  // cifrada desde el modelo
            $t->string('estado', 20)->default('activo');
            $t->text('notas')->nullable();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('vinculos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $t->string('tipo', 20);                   // independiente | empleado
            $t->string('cargo');
            $t->date('fecha_inicio');
            $t->date('fecha_fin')->nullable();
            $t->string('remuneracion', 20)->nullable(); // tarifa | salario
            $t->decimal('monto', 12, 2)->nullable();
            $t->decimal('comision_pct', 5, 2)->nullable();
            $t->string('motivo_fin')->nullable();
            $t->text('notas')->nullable();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['colaborador_id', 'fecha_inicio']);
        });

        DB::statement("alter table vinculos add constraint vinculos_tipo_check check (tipo in ('independiente','empleado'))");
        DB::statement('alter table vinculos add constraint vinculos_fechas_check check (fecha_fin is null or fecha_fin >= fecha_inicio)');
        DB::statement('create unique index vinculos_activo_unique on vinculos (colaborador_id) where fecha_fin is null');

        Schema::create('documentos_colaborador', function (Blueprint $t) {
            $t->id();
            $t->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $t->string('tipo');
            $t->date('vencimiento')->nullable()->index();
            $t->text('notas')->nullable();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
        });

        // Los técnicos existentes pasan a ser colaboradores con vínculo independiente.
        DB::statement("
            insert into colaboradores (user_id, nombre, rtn, telefono, estado, created_at, updated_at)
            select t.user_id, u.name, t.rtn, t.telefono,
                   case when t.deleted_at is null then 'activo' else 'inactivo' end, now(), now()
            from tecnicos t join users u on u.id = t.user_id
        ");
        DB::statement("
            insert into vinculos (colaborador_id, tipo, cargo, fecha_inicio, fecha_fin, created_at, updated_at)
            select c.id, 'independiente', 'Técnico',
                   coalesce(t.created_at::date, current_date),
                   case when t.deleted_at is not null then greatest(t.deleted_at::date, coalesce(t.created_at::date, current_date)) end,
                   now(), now()
            from colaboradores c join tecnicos t on t.user_id = c.user_id
        ");

        // Mismo patrón de seguridad que el resto de tablas.
        foreach (['colaboradores', 'vinculos', 'documentos_colaborador'] as $tabla) {
            DB::statement("alter table {$tabla} enable row level security");
            DB::statement("create policy interno_total on {$tabla} using (app_es_interno()) with check (app_es_interno())");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_colaborador');
        Schema::dropIfExists('vinculos');
        Schema::dropIfExists('colaboradores');
    }
};
