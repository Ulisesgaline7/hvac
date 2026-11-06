<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentosContenidoSeeder extends Seeder
{
    public function run(): void
    {
        $archivos = glob(database_path('seeders/documentos/*.php')) ?: [];
        sort($archivos);

        foreach ($archivos as $archivo) {
            foreach ((require $archivo) as $codigo => $html) {
                $d = DB::table('documentos_catalogo')->where('codigo', $codigo)->first();

                if (! $d) {
                    $this->command?->warn("$codigo no existe en el catálogo");
                    continue;
                }
                if (filled($d->contenido)) {
                    $this->command?->line("$codigo ya tiene contenido, se omite");
                    continue;
                }

                DB::transaction(function () use ($d, $html) {
                    $html = trim($html);
                    DB::table('documentos_catalogo')->where('id', $d->id)->update([
                        'contenido'  => $html,
                        'updated_at' => now(),
                    ]);
                    DB::table('documento_versiones')->insert([
                        'documento_id' => $d->id,
                        'version'      => $d->version ?: '1.0',
                        'contenido'    => $html,
                        'created_at'   => now(),
                    ]);
                });

                $this->command?->info("$codigo cargado");
            }
        }
    }
}
