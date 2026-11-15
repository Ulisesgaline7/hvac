<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('alter table vinculos drop constraint vinculos_tipo_check');
        DB::statement("alter table vinculos add constraint vinculos_tipo_check check (tipo in ('independiente','empleado','externo'))");
    }

    public function down(): void
    {
        DB::statement('alter table vinculos drop constraint vinculos_tipo_check');
        DB::statement("alter table vinculos add constraint vinculos_tipo_check check (tipo in ('independiente','empleado'))");
    }
};
