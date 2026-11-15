<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $clima = (int) DB::table('divisiones')->where('codigo', 'CLIMA')->value('id');

        Schema::table('reglas_tarifario', function (Blueprint $t) {
            $t->foreignId('division_id')->nullable()->constrained('divisiones')->nullOnDelete();
        });

        DB::table('reglas_tarifario')->update(['division_id' => $clima]);
        DB::statement("alter table reglas_tarifario alter column division_id set default {$clima}");
        DB::statement('alter table reglas_tarifario drop constraint reglas_tarifario_clave_unique');
        DB::statement('alter table reglas_tarifario add constraint reglas_tarifario_division_clave_unique unique (division_id, clave)');
    }

    public function down(): void
    {
        DB::statement('alter table reglas_tarifario drop constraint reglas_tarifario_division_clave_unique');
        DB::statement('alter table reglas_tarifario add constraint reglas_tarifario_clave_unique unique (clave)');
        Schema::table('reglas_tarifario', function (Blueprint $t) {
            $t->dropConstrainedForeignId('division_id');
        });
    }
};
