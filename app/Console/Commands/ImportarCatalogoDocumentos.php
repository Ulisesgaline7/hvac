<?php

namespace App\Console\Commands;

use App\Models\DocumentoCatalogo;
use Illuminate\Console\Command;

class ImportarCatalogoDocumentos extends Command
{
    protected $signature = 'documentos:importar {archivo=database/data/catalogo-documentos.csv}';
    protected $description = 'Carga el catálogo de documentos desde un CSV (solo crea los que faltan).';

    private const DISENO = [
        'pdf_generado' => 'comercial',
        'plantilla_contrato' => 'contrato',
        'formulario' => 'formulario',
        'repositorio' => 'controlado',
        'plantilla_formulario' => 'registro',
        'registro' => 'registro',
        'pantalla_datos' => 'pantalla',
        'calculo' => 'pantalla',
        'reporte' => 'pantalla',
        'vista_automatica' => 'pantalla',
    ];

    public function handle(): int
    {
        $ruta = base_path($this->argument('archivo'));
        if (! is_file($ruta)) {
            $this->error("No existe: {$ruta}");
            return self::FAILURE;
        }

        $f = fopen($ruta, 'r');
        $cab = fgetcsv($f, 0, ',', '"', '');
        $cab = array_map(fn ($c) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $c)), $cab);

        $nuevos = 0;
        $existentes = 0;

        while (($fila = fgetcsv($f, 0, ',', '"', '')) !== false) {
            if (count($fila) !== count($cab)) {
                continue;
            }
            $r = array_combine($cab, $fila);
            $v = fn (string $k) => filled($r[$k] ?? null) ? trim($r[$k]) : null;

            $nombre = trim($r['nombre']);
            $interno = null;
            if (preg_match('/^(PRO(?: [A-Z]+)+ \d+) (.+)$/u', $nombre, $m)) {
                $interno = str_replace(' ', '-', $m[1]);
                $nombre = $m[2];
            }

            $tipo = $v('tipo_digitalizacion');
            $fuera = $tipo === 'fuera';

            $doc = DocumentoCatalogo::firstOrCreate(
                ['codigo' => $r['codigo']],
                [
                    'codigo_interno' => $interno,
                    'archivo' => $v('archivo'),
                    'nombre' => $nombre,
                    'grupo' => $v('grupo'),
                    'tipo_digitalizacion' => $tipo,
                    'diseno' => self::DISENO[$tipo] ?? null,
                    'modulo' => $v('modulo'),
                    'entrega' => $v('entrega'),
                    'prioridad' => $v('prioridad'),
                    'requisito' => $v('requisito'),
                    'tabla_propuesta' => $v('tabla_propuesta'),
                    'estado' => $fuera ? 'fuera' : ($v('estado') ?: 'pendiente'),
                    'notas' => $v('notas'),
                ]
            );

            $doc->wasRecentlyCreated ? $nuevos++ : $existentes++;
        }
        fclose($f);

        $this->info("Nuevos: {$nuevos} · Ya existían: {$existentes}");
        return self::SUCCESS;
    }
}
