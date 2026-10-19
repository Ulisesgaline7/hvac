<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factura_cuotas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('factura_id')->constrained('facturas')->cascadeOnDelete();
            $t->foreignId('cliente_id')->constrained('clientes');
            $t->unsignedSmallInteger('numero');
            $t->decimal('monto', 12, 2);
            $t->date('vence');
            $t->timestamps();

            $t->unique(['factura_id', 'numero']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE factura_cuotas ADD CONSTRAINT cuota_monto_check CHECK (monto > 0)');
            DB::statement('ALTER TABLE factura_cuotas ENABLE ROW LEVEL SECURITY');
            DB::statement('CREATE POLICY interno_total ON factura_cuotas USING (app_es_interno()) WITH CHECK (app_es_interno())');
            DB::statement('CREATE POLICY cliente_lectura ON factura_cuotas FOR SELECT USING (cliente_id = app_cliente_id())');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_cuotas');
    }
};
