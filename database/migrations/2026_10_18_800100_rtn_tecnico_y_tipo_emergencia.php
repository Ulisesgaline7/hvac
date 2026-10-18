<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tecnicos', fn (Blueprint $t) => $t->string('rtn')->nullable()->after('telefono'));

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
ALTER TABLE ordenes_trabajo DROP CONSTRAINT ot_tipo_check;
ALTER TABLE ordenes_trabajo ADD CONSTRAINT ot_tipo_check
    CHECK (tipo IN ('preventivo','correctivo','instalacion','emergencia'));
SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
ALTER TABLE ordenes_trabajo DROP CONSTRAINT ot_tipo_check;
ALTER TABLE ordenes_trabajo ADD CONSTRAINT ot_tipo_check
    CHECK (tipo IN ('preventivo','correctivo','instalacion'));
SQL);
        }
        Schema::table('tecnicos', fn (Blueprint $t) => $t->dropColumn('rtn'));
    }
};
