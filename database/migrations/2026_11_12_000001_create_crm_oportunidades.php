<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oportunidades', function (Blueprint $t) {
            $t->id();
            $t->string('titulo');
            $t->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $t->string('prospecto_nombre')->nullable();
            $t->string('prospecto_telefono', 30)->nullable();
            $t->string('prospecto_email')->nullable();
            $t->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('division_id')->nullable()->constrained('divisiones')->nullOnDelete();
            $t->string('etapa', 20)->default('nuevo');
            $t->string('origen', 30)->nullable();
            $t->decimal('monto_estimado', 12, 2)->default(0);
            $t->unsignedTinyInteger('probabilidad')->default(10);
            $t->date('cierre_esperado')->nullable();
            $t->string('motivo_perdida')->nullable();
            $t->timestamp('cerrada_en')->nullable();
            $t->text('notas')->nullable();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['responsable_id', 'etapa']);
        });
        DB::statement("alter table oportunidades add constraint oportunidades_etapa_check check (etapa in ('nuevo','contactado','levantamiento','cotizado','negociacion','ganada','perdida'))");

        Schema::create('oportunidad_actividades', function (Blueprint $t) {
            $t->id();
            $t->foreignId('oportunidad_id')->constrained('oportunidades')->cascadeOnDelete();
            $t->string('tipo', 20)->default('nota');
            $t->text('descripcion');
            $t->timestamp('vence_en')->nullable();
            $t->timestamp('hecha_en')->nullable();
            $t->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['responsable_id', 'hecha_en', 'vence_en']);
        });
        DB::statement("alter table oportunidad_actividades add constraint opact_tipo_check check (tipo in ('nota','llamada','visita','whatsapp','correo','tarea'))");

        foreach (['oportunidades', 'oportunidad_actividades'] as $tabla) {
            DB::statement("alter table $tabla enable row level security");
            DB::statement("create policy interno_total on $tabla using (app_es_interno()) with check (app_es_interno())");
        }

        Schema::table('contactos', fn (Blueprint $t) => $t->foreignId('oportunidad_id')->nullable()->constrained('oportunidades')->nullOnDelete());
        Schema::table('cotizaciones', fn (Blueprint $t) => $t->foreignId('oportunidad_id')->nullable()->constrained('oportunidades')->nullOnDelete());
    }

    public function down(): void
    {
        Schema::table('cotizaciones', fn (Blueprint $t) => $t->dropConstrainedForeignId('oportunidad_id'));
        Schema::table('contactos', fn (Blueprint $t) => $t->dropConstrainedForeignId('oportunidad_id'));
        Schema::dropIfExists('oportunidad_actividades');
        Schema::dropIfExists('oportunidades');
    }
};
