<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documentos_catalogo', function (Blueprint $t) {
            $t->id();
            $t->string('codigo')->unique();
            $t->string('codigo_interno')->nullable();
            $t->string('archivo')->nullable();
            $t->string('nombre');
            $t->string('grupo')->nullable();
            $t->string('tipo_digitalizacion')->nullable();
            $t->string('diseno')->nullable();
            $t->string('modulo')->nullable();
            $t->string('entrega')->nullable();
            $t->string('prioridad')->nullable();
            $t->string('requisito')->nullable();
            $t->string('tabla_propuesta')->nullable();
            $t->string('estado')->default('pendiente');
            $t->string('version')->default('1.0');
            $t->date('vigente_desde')->nullable();
            $t->date('proxima_revision')->nullable();
            $t->string('elaborado_por')->nullable();
            $t->string('revisado_por')->nullable();
            $t->string('aprobado_por')->nullable();
            $t->longText('contenido')->nullable();
            $t->text('notas')->nullable();
            $t->unsignedBigInteger('actualizado_por')->nullable();
            $t->timestamps();
            $t->index('grupo');
            $t->index('estado');
        });

        Schema::create('documento_versiones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('documento_id')->constrained('documentos_catalogo')->cascadeOnDelete();
            $t->string('version')->nullable();
            $t->longText('contenido')->nullable();
            $t->unsignedBigInteger('creado_por')->nullable();
            $t->timestamp('created_at')->nullable();
        });

        if (DB::getDriverName() === 'pgsql') {
            $rol = config('database.connections.pgsql_app.username') ?: 'usuario_app';
            if (DB::table('pg_roles')->where('rolname', $rol)->exists()) {
                foreach (['documentos_catalogo', 'documento_versiones'] as $tabla) {
                    DB::statement("GRANT SELECT, INSERT, UPDATE, DELETE ON {$tabla} TO \"{$rol}\"");
                    DB::statement("GRANT USAGE, SELECT ON SEQUENCE {$tabla}_id_seq TO \"{$rol}\"");
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_versiones');
        Schema::dropIfExists('documentos_catalogo');
    }
};
