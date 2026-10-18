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
            ['Split', 'aire_acondicionado', 'HVAC'],
            ['Cassette', 'aire_acondicionado', 'HVAC'],
            ['Paquete', 'aire_acondicionado', 'HVAC'],
            ['Chiller', 'aire_acondicionado', 'HVAC'],
            ['Cuarto frío', 'cuarto_frio', 'REF'],
            ['Congelador', 'cuarto_frio', 'REF'],
            ['Mesa fría', 'refrigeracion_comercial', 'REF'],
            ['Vitrina', 'refrigeracion_comercial', 'REF'],
            ['Máquina de hielo', 'refrigeracion_comercial', 'REF'],
        ];

        foreach ($tipos as [$nombre, $familia, $prefijo]) {
            TipoEquipo::firstOrCreate(['nombre' => $nombre], ['familia' => $familia, 'prefijo_codigo' => $prefijo]);
        }
    }
}
