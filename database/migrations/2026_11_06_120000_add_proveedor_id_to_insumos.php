<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('insumos') && ! Schema::hasColumn('insumos', 'proveedor_id')) {
            Schema::table('insumos', function (Blueprint $t) {
                $t->foreignId('proveedor_id')->nullable()->after('categoria')->constrained('proveedores')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('insumos', 'proveedor_id')) {
            Schema::table('insumos', function (Blueprint $t) {
                $t->dropConstrainedForeignId('proveedor_id');
            });
        }
    }
};
