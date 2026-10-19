<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columnas = [
        'cai', 'rango_inicial', 'rango_final', 'fecha_limite_emision',
        'establecimiento', 'punto_emision', 'tipo_documento', 'rtn_cliente',
    ];

    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $t) {
            // Todos nulos hasta que exista autorización de numeración (CAI) del SAR.
            $t->string('cai', 64)->nullable();
            $t->string('rango_inicial', 32)->nullable();
            $t->string('rango_final', 32)->nullable();
            $t->date('fecha_limite_emision')->nullable();
            $t->string('establecimiento', 3)->nullable();
            $t->string('punto_emision', 3)->nullable();
            $t->string('tipo_documento', 2)->nullable();
            $t->string('rtn_cliente', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('facturas', fn (Blueprint $t) => $t->dropColumn($this->columnas));
    }
};
