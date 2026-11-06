<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pagos_tecnico', function (Blueprint $t) {
            $t->timestamp('aprobado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pagos_tecnico', function (Blueprint $t) {
            $t->dropColumn('aprobado_en');
        });
    }
};
