<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $tablas = ['whatsapp_identidades', 'whatsapp_conversaciones', 'whatsapp_mensajes'];

    public function up(): void
    {
        Schema::create('whatsapp_identidades', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tecnico_id')->unique()->constrained('tecnicos')->cascadeOnDelete();
            $t->string('numero', 20)->unique();           // 504 + 8 dígitos, sin "+"
            $t->boolean('acepta')->default(false);
            $t->timestamp('acepta_en')->nullable();
            $t->string('pin_hash')->nullable();
            $t->unsignedSmallInteger('pin_intentos')->default(0);
            $t->timestamp('pin_bloqueado_hasta')->nullable();
            $t->timestamp('pin_cambiado_en')->nullable();
            $t->timestamps();
        });

        Schema::create('whatsapp_conversaciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tecnico_id')->unique()->constrained('tecnicos')->cascadeOnDelete();
            $t->string('estado', 40)->default('inicio');
            $t->jsonb('contexto')->nullable();
            $t->foreignId('orden_trabajo_id')->nullable()->constrained('ordenes_trabajo')->nullOnDelete();
            $t->timestamp('expira_en')->nullable();
            $t->timestamps();
        });

        Schema::create('whatsapp_mensajes', function (Blueprint $t) {
            $t->id();
            $t->string('wa_id')->unique();                 // id de Meta: evita procesar un reintento dos veces
            $t->foreignId('tecnico_id')->nullable()->constrained('tecnicos')->nullOnDelete();
            $t->string('direccion', 8);                    // entrada | salida
            $t->string('tipo', 20);
            $t->text('contenido')->nullable();             // nunca se guarda el contenido de un PIN
            $t->timestamp('created_at')->useCurrent();
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $rol = config('database.connections.pgsql_app.username') ?: 'usuario_app';
        $existe = DB::selectOne('select 1 as ok from pg_roles where rolname = ?', [$rol]);

        foreach ($this->tablas as $tabla) {
            DB::statement("ALTER TABLE {$tabla} ENABLE ROW LEVEL SECURITY");
            DB::statement("CREATE POLICY interno_total ON {$tabla} USING (app_es_interno()) WITH CHECK (app_es_interno())");
            if ($existe) {
                DB::statement("GRANT SELECT, INSERT, UPDATE, DELETE ON {$tabla} TO \"{$rol}\"");
                DB::statement("GRANT USAGE, SELECT ON SEQUENCE {$tabla}_id_seq TO \"{$rol}\"");
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tablas) as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
