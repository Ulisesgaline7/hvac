<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plantillas_formulario', function (Blueprint $t) {
            // instalacion | mantenimiento | correctivo | diagnostico
            $t->string('tipo_trabajo')->default('mantenimiento');
        });

        DB::table('plantillas_formulario')->where('familia', 'instalacion')->update(['tipo_trabajo' => 'instalacion']);
    }

    public function down(): void
    {
        Schema::table('plantillas_formulario', fn (Blueprint $t) => $t->dropColumn('tipo_trabajo'));
    }
};
