<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tablas = ['cotizaciones', 'ordenes_trabajo'];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $t) use ($tabla) {
                if (! Schema::hasColumn($tabla, 'dias_credito')) {
                    $t->smallInteger('dias_credito')->nullable();   // null = usar config/cobros.php
                }
                if (! Schema::hasColumn($tabla, 'anticipo_pct')) {
                    $t->decimal('anticipo_pct', 5, 2)->nullable();  // null = usar config/cobros.php
                }
            });

            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE {$tabla} ADD CONSTRAINT {$tabla}_dias_credito_check CHECK (dias_credito IS NULL OR dias_credito = 0 OR dias_credito BETWEEN 15 AND 30)");
                DB::statement("ALTER TABLE {$tabla} ADD CONSTRAINT {$tabla}_anticipo_pct_check CHECK (anticipo_pct IS NULL OR anticipo_pct BETWEEN 0 AND 100)");
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE {$tabla} DROP CONSTRAINT IF EXISTS {$tabla}_dias_credito_check");
                DB::statement("ALTER TABLE {$tabla} DROP CONSTRAINT IF EXISTS {$tabla}_anticipo_pct_check");
            }
            Schema::table($tabla, fn (Blueprint $t) => $t->dropColumn(['dias_credito', 'anticipo_pct']));
        }
    }
};
