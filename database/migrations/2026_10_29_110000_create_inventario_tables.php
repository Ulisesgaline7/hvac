<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('insumos', function (Blueprint $t) {
            $t->id();
            $t->string('codigo', 40)->unique();
            $t->string('nombre', 160);
            $t->string('categoria', 60)->nullable();
            $t->string('unidad', 20)->default('unidad');
            $t->decimal('costo', 12, 2)->default(0);
            $t->decimal('precio_venta', 12, 2)->nullable();
            $t->decimal('stock', 12, 2)->default(0);
            $t->decimal('stock_minimo', 12, 2)->default(0);
            $t->string('ubicacion', 80)->nullable();
            $t->text('notas')->nullable();
            $t->boolean('activo')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('movimientos_inventario', function (Blueprint $t) {
            $t->id();
            $t->foreignId('insumo_id')->constrained('insumos');
            $t->string('tipo', 10);
            $t->decimal('cantidad', 12, 2);
            $t->decimal('costo_unitario', 12, 2)->nullable();
            $t->decimal('stock_resultante', 12, 2);
            $t->foreignId('orden_trabajo_id')->nullable()->constrained('ordenes_trabajo')->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('motivo')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
        Schema::dropIfExists('insumos');
    }
};
