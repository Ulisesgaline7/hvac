<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Solo proveedores y gastos.proveedor_id. Insumos y movimientos los crea 2026_10_29_110000. */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('proveedores')) {
            Schema::create('proveedores', function (Blueprint $t) {
                $t->id();
                $t->string('nombre', 150);
                $t->string('rtn', 20)->nullable();
                $t->string('contacto', 100)->nullable();
                $t->string('telefono', 30)->nullable();
                $t->string('email', 120)->nullable();
                $t->string('direccion')->nullable();
                $t->text('notas')->nullable();
                $t->boolean('activo')->default(true);
                $t->unsignedBigInteger('creado_por')->nullable();
                $t->timestamps();
            });
        }

        if (Schema::hasTable('gastos') && ! Schema::hasColumn('gastos', 'proveedor_id')) {
            Schema::table('gastos', fn (Blueprint $t) => $t->unsignedBigInteger('proveedor_id')->nullable()->after('proveedor'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('gastos', 'proveedor_id')) {
            Schema::table('gastos', fn (Blueprint $t) => $t->dropColumn('proveedor_id'));
        }
        Schema::dropIfExists('proveedores');
    }
};
