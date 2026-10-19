<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratos', function (Blueprint $t) {
            $t->decimal('cuota_monto', 12, 2)->nullable();       // antes de ISV, por periodo
            $t->string('periodicidad')->nullable();              // mensual | trimestral | semestral | anual
        });

        Schema::table('facturas', function (Blueprint $t) {
            $t->foreignId('contrato_id')->nullable()->constrained('contratos')->nullOnDelete();
            $t->date('periodo')->nullable();
            $t->unique(['contrato_id', 'periodo']);
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $t) {
            $t->dropUnique(['contrato_id', 'periodo']);
            $t->dropConstrainedForeignId('contrato_id');
            $t->dropColumn('periodo');
        });
        Schema::table('contratos', fn (Blueprint $t) => $t->dropColumn(['cuota_monto', 'periodicidad']));
    }
};
