<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ajustes_documentos', function (Blueprint $t) {
            $t->id();
            $t->string('clave')->unique();
            $t->text('valor')->nullable();
            $t->timestamps();
        });

        // La app usa un usuario sin permisos de dueño: hay que darle acceso a la tabla nueva.
        if (DB::getDriverName() === 'pgsql') {
            $rol = config('database.connections.pgsql_app.username') ?: 'usuario_app';
            if (DB::table('pg_roles')->where('rolname', $rol)->exists()) {
                DB::statement("GRANT SELECT, INSERT, UPDATE, DELETE ON ajustes_documentos TO \"{$rol}\"");
                DB::statement("GRANT USAGE, SELECT ON SEQUENCE ajustes_documentos_id_seq TO \"{$rol}\"");
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes_documentos');
    }
};
