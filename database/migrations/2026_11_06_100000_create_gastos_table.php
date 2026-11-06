<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('gastos')) {
            return;
        }

        Schema::create('gastos', function (Blueprint $t) {
            $t->id();
            $t->date('fecha')->index();
            $t->string('proveedor', 150);
            $t->string('concepto');
            $t->string('categoria', 30)->index();
            $t->string('factura_proveedor', 60)->nullable();
            $t->decimal('subtotal', 12, 2);
            $t->decimal('isv', 12, 2)->default(0);
            $t->decimal('total', 12, 2);
            $t->string('metodo', 30)->nullable();
            $t->text('notas')->nullable();
            $t->unsignedBigInteger('creado_por')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
