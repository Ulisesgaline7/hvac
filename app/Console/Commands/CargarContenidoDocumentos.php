<?php

namespace App\Console\Commands;

use App\Models\DocumentoCatalogo;
use Illuminate\Console\Command;

class CargarContenidoDocumentos extends Command
{
    protected $signature = 'documentos:cargar-contenido
        {carpeta=database/data/contenido : Carpeta con los .html (mismo nombre que el PDF del catálogo)}
        {--aplicar : Escribe los cambios; sin esta opción solo simula}
        {--solo= : Procesa un solo documento (código DOC-001 o nombre de archivo)}
        {--sobrescribir : También reemplaza los que ya tienen contenido (la versión anterior queda en el historial)}
        {--nueva-version= : Versión a registrar en los documentos cargados (ej. 1.1)}
        {--estado= : Estado a asignar a los documentos cargados (ej. en_progreso)}';

    protected $description = 'Carga el texto (HTML) de los documentos en la biblioteca. Por defecto solo llena los que están vacíos y simula.';

    public function handle(): int
    {
        $dir = base_path($this->argument('carpeta'));
        $archivos = glob($dir . '/*.html') ?: [];
        if (! $archivos) {
            $this->error("No hay archivos .html en {$dir}");
            return self::FAILURE;
        }

        $estado = $this->option('estado');
        if ($estado && ! array_key_exists($estado, DocumentoCatalogo::ESTADOS)) {
            $this->error('Estado no válido. Usa: ' . implode(', ', array_keys(DocumentoCatalogo::ESTADOS)));
            return self::FAILURE;
        }

        $porArchivo = DocumentoCatalogo::all()->keyBy(
            fn ($d) => mb_strtolower(pathinfo((string) $d->archivo, PATHINFO_FILENAME))
        );

        $solo = mb_strtolower((string) $this->option('solo'));
        $aplicar = (bool) $this->option('aplicar');
        $c = ['cargados' => 0, 'ya_tenian' => 0, 'fuera' => 0, 'sin_catalogo' => 0, 'vacios' => 0];

        foreach ($archivos as $ruta) {
            $stem = mb_strtolower(pathinfo($ruta, PATHINFO_FILENAME));
            $doc = $porArchivo[$stem] ?? null;

            if ($solo !== '' && ! ($doc && (mb_strtolower($doc->codigo) === $solo || $stem === $solo))) {
                continue;
            }
            if (! $doc) {
                $this->warn("Sin fila en el catálogo: {$stem}");
                $c['sin_catalogo']++;
                continue;
            }
            if ($doc->estado === 'fuera') {
                $c['fuera']++;
                continue;
            }

            $html = trim((string) file_get_contents($ruta));
            if ($html === '') {
                $c['vacios']++;
                continue;
            }
            if (filled($doc->contenido) && ! $this->option('sobrescribir')) {
                $c['ya_tenian']++;
                continue;
            }

            $this->line(($aplicar ? '[ok] ' : '[simulación] ') . "{$doc->codigo} · {$doc->nombre} · " . strlen($html) . ' caracteres');

            if ($aplicar) {
                $cambios = ['contenido' => $html];
                if ($v = $this->option('nueva-version')) {
                    $cambios['version'] = $v;
                }
                if ($estado) {
                    $cambios['estado'] = $estado;
                }
                // El modelo guarda solo la versión anterior en el historial si ya había contenido.
                $doc->update($cambios);
            }
            $c['cargados']++;
        }

        $sinHtml = $porArchivo->filter(
            fn ($d, $k) => $d->estado !== 'fuera' && ! is_file($dir . '/' . $k . '.html')
                && ! in_array(mb_strtolower($k) . '.html', array_map('mb_strtolower', array_map('basename', $archivos)), true)
        )->count();

        $this->newLine();
        $this->info(($aplicar ? 'Cargados' : 'Se cargarían') . ": {$c['cargados']} · Ya tenían contenido (omitidos): {$c['ya_tenian']} · Fuera del sistema: {$c['fuera']} · Sin fila en catálogo: {$c['sin_catalogo']} · Catálogo sin HTML: {$sinHtml}");
        if (! $aplicar) {
            $this->comment('Fue una simulación. Agrega --aplicar para escribir.');
        }

        return self::SUCCESS;
    }
}
