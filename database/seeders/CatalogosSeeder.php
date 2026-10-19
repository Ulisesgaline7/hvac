<?php

namespace Database\Seeders;

use App\Models\Marca;
use App\Models\Refrigerante;
use App\Models\TipoEquipo;
use Illuminate\Database\Seeder;

class CatalogosSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Carrier', 'Trane', 'York', 'LG', 'Samsung', 'Daikin', 'Mirage', 'Copeland', 'Bohn', 'Hussmann'] as $m) {
            Marca::firstOrCreate(['nombre' => $m]);
        }

        foreach (['R-410A', 'R-22', 'R-134a', 'R-404A', 'R-407C', 'R-32', 'R-290', 'R-600a'] as $r) {
            Refrigerante::firstOrCreate(['nombre' => $r]);
        }

        $tipos = [
            ['Split', 'aire_acondicionado', 'EQ'],
            ['Cassette', 'aire_acondicionado', 'EQ'],
            ['Paquete', 'aire_acondicionado', 'EQ'],
            ['Chiller', 'aire_acondicionado', 'EQ'],
            ['Cuarto frío', 'cuartos_frios', 'EQ'],
            ['Congelador', 'cuartos_frios', 'EQ'],
            ['Mesa fría', 'refrigeracion_comercial', 'EQ'],
            ['Vitrina', 'refrigeracion_comercial', 'EQ'],
            ['Máquina de hielo', 'refrigeracion_comercial', 'EQ'],
        ];

        foreach ($tipos as [$nombre, $familia, $prefijo]) {
            TipoEquipo::firstOrCreate(['nombre' => $nombre], ['familia' => $familia, 'prefijo_codigo' => $prefijo]);
        }
    }
}
