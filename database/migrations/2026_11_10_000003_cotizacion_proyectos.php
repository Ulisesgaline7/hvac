<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('alter table cotizacion_lineas drop constraint cotl_tipo_check');
        DB::statement("alter table cotizacion_lineas add constraint cotl_tipo_check check (tipo in ('servicio','repuesto','material','equipo','mano_de_obra','otro'))");

        Schema::table('cotizacion_lineas', function (Blueprint $t) {
            $t->string('capitulo', 120)->nullable();
            $t->string('marca', 120)->nullable();
            $t->string('modelo', 120)->nullable();
        });

        Schema::create('cotizacion_linea_costos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cotizacion_linea_id')->unique()->constrained('cotizacion_lineas')->cascadeOnDelete();
            $t->decimal('costo_unitario', 12, 2)->default(0);
            $t->foreignId('insumo_id')->nullable()->constrained('insumos')->nullOnDelete();
            $t->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $t->timestamps();
        });

        DB::statement('alter table cotizacion_linea_costos enable row level security');
        DB::statement('create policy interno_total on cotizacion_linea_costos using (app_es_interno()) with check (app_es_interno())');
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_linea_costos');
        Schema::table('cotizacion_lineas', function (Blueprint $t) {
            $t->dropColumn(['capitulo', 'marca', 'modelo']);
        });
        DB::statement('alter table cotizacion_lineas drop constraint cotl_tipo_check');
        DB::statement("alter table cotizacion_lineas add constraint cotl_tipo_check check (tipo in ('servicio','repuesto'))");
    }
};
