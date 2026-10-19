<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ordenes_trabajo', 'visita_cobro')) {
            Schema::table('ordenes_trabajo', function (Blueprint $t) {
                // null = aplicar la regla de config/visita.php; gratis | cobrar = excepción manual
                $t->string('visita_cobro', 20)->nullable();
                $t->decimal('visita_monto', 12, 2)->nullable();
            });
        }

        if (! Schema::hasColumn('cotizaciones', 'visita_ot_id')) {
            Schema::table('cotizaciones', function (Blueprint $t) {
                $t->foreignId('visita_ot_id')->nullable()->constrained('ordenes_trabajo')->nullOnDelete();
                $t->string('visita_tratamiento', 20)->nullable(); // bonificar | cobrar | gratis
                $t->decimal('visita_monto', 12, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cotizaciones', 'visita_ot_id')) {
            Schema::table('cotizaciones', function (Blueprint $t) {
                $t->dropConstrainedForeignId('visita_ot_id');
                $t->dropColumn(['visita_tratamiento', 'visita_monto']);
            });
        }
        if (Schema::hasColumn('ordenes_trabajo', 'visita_cobro')) {
            Schema::table('ordenes_trabajo', fn (Blueprint $t) => $t->dropColumn(['visita_cobro', 'visita_monto']));
        }
    }
};
