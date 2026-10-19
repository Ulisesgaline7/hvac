<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $max = (int) (DB::table('equipos')->max('id') ?? 0);

        foreach (DB::table('equipos')->orderBy('id')->get(['id']) as $equipo) {
            DB::table('equipos')->where('id', $equipo->id)->update(['codigo' => sprintf('EQ-%04d', $equipo->id)]);
        }

        DB::table('codigo_secuencias')->whereIn('prefijo', ['HVAC', 'REF'])->delete();
        DB::table('codigo_secuencias')->updateOrInsert(['prefijo' => 'EQ'], ['ultimo' => $max]);
    }

    public function down(): void
    {
    }
};
