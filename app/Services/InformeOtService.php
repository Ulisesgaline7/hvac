<?php

namespace App\Services;

use App\Models\ActaConformidad;
use App\Models\Cliente;
use App\Models\Informe;
use App\Models\OrdenTrabajo;
use App\Models\OrdenTrabajoEquipo;
use App\Models\Sucursal;
use App\Models\TipoEquipo;
use Dompdf\Dompdf;

class InformeOtService
{
    public function pdf(OrdenTrabajo $ot, ?Informe $informe = null): string
    {
        $dompdf = new Dompdf(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false]);
        $dompdf->loadHtml(view('pdf.informe-ot', $this->datos($ot, $informe))->render());
        $dompdf->setPaper('letter');
        $dompdf->render();

        return $dompdf->output();
    }

    public function datos(OrdenTrabajo $ot, ?Informe $informe = null): array
    {
        $lineas = OrdenTrabajoEquipo::where('orden_trabajo_id', $ot->id)
            ->with(['equipo', 'ficha.version.plantilla', 'media'])
            ->orderBy('id')
            ->get()
            ->map(function ($l) {
                $ficha = $l->ficha;
                $tipoId = $l->equipo?->tipo_equipo_id ?? $l->tipo_equipo_id;

                return [
                    'codigo' => $l->equipo?->codigo ?? '—',
                    'tipo' => $tipoId ? TipoEquipo::find($tipoId)?->nombre : null,
                    'estado_resultante' => $l->estado_resultante,
                    'observaciones' => $l->observaciones,
                    'tiene_ficha' => (bool) $ficha,
                    'fotos' => $this->fotos($l),
                    'firmas' => $this->firmas($l),
                    'completa' => (bool) $ficha?->completa,
                    'alertas' => $ficha?->alertas ?? [],
                    'plantilla' => $ficha?->version?->plantilla?->nombre ?? null,
                    'filas' => $ficha ? $this->filas($ficha->version->definicion ?? [], $ficha->respuestas ?? []) : [],
                ];
            });

        $urlVerificacion = $informe?->token_verificacion ? route('informe.verificar', $informe->token_verificacion) : null;

        return [
            'ot' => $ot,
            'cliente' => Cliente::find($ot->cliente_id),
            'sucursal' => Sucursal::find($ot->sucursal_id),
            'tecnico' => $ot->tecnico?->user?->name,
            'lineas' => $lineas,
            'generado' => now()->format('d/m/Y H:i'),
            'acta' => $this->acta($ot),
            'qr' => $urlVerificacion ? app(GeneradorQr::class)->dataUriDeUrl($urlVerificacion) : null,
            'verificacion_url' => $urlVerificacion,
        ];
    }

    /** Fotos de la línea como data URI (versión reducida si existe). */
    private function fotos(OrdenTrabajoEquipo $l): array
    {
        $titulos = ['foto_antes' => 'Antes', 'foto_despues' => 'Después'];

        return $l->media
            ->filter(fn ($m) => str_starts_with($m->collection_name, 'foto'))
            ->map(function ($m) use ($titulos) {
                $ruta = $m->getPath('informe');
                if (! is_file($ruta)) {
                    $ruta = $m->getPath();
                }

                if (! is_file($ruta)) {
                    return null;
                }

                return [
                    'titulo' => $titulos[$m->collection_name] ?? ucfirst(str_replace('_', ' ', substr($m->collection_name, 5))),
                    'src' => 'data:' . $m->mime_type . ';base64,' . base64_encode(file_get_contents($ruta)),
                ];
            })->filter()->values()->all();
    }

    /** Firmas de la línea como data URI. */
    private function firmas(OrdenTrabajoEquipo $l): array
    {
        $titulos = ['firma_tecnico' => 'Firma del técnico', 'firma_cliente' => 'Firma del cliente'];

        return $l->media
            ->filter(fn ($m) => str_starts_with($m->collection_name, 'firma'))
            ->filter(fn ($m) => is_file($m->getPath()))
            ->map(fn ($m) => [
                'titulo' => $titulos[$m->collection_name] ?? ucfirst(str_replace('_', ' ', $m->collection_name)),
                'src' => 'data:image/png;base64,' . base64_encode(file_get_contents($m->getPath())),
            ])->values()->all();
    }

    /** Una fila por campo de la plantilla (omite foto y firma, que llegan con el módulo de evidencias). */
    private function filas(array $campos, array $resp): array
    {
        $filas = [];

        foreach ($campos as $c) {
            if (in_array($c['tipo'], ['foto', 'firma'], true)) {
                continue;
            }

            $v = $resp[$c['clave']] ?? null;
            $fuera = false;

            if ($c['tipo'] === 'numero' && is_numeric($v)) {
                $min = $c['min'] ?? null;
                $max = $c['max'] ?? null;
                $fuera = ($min !== null && $min !== '' && $v < $min) || ($max !== null && $max !== '' && $v > $max);
            }

            $texto = match (true) {
                $v === null || $v === '' || $v === [] => '—',
                $c['tipo'] === 'casilla' => $v ? 'Sí' : 'No',
                is_array($v) => implode(', ', $v),
                $c['tipo'] === 'numero' => $v . (! empty($c['unidad']) ? ' ' . $c['unidad'] : ''),
                default => (string) $v,
            };

            $filas[] = ['etiqueta' => $c['etiqueta'], 'valor' => $texto, 'fuera' => $fuera, 'nota' => $c['tipo'] === 'nota'];
        }

        return $filas;
    }

    /** Acta de conformidad lista para imprimir, o null si la OT no tiene. */
    private function acta(OrdenTrabajo $ot): ?array
    {
        $a = ActaConformidad::where('orden_trabajo_id', $ot->id)->latest('id')->first();
        if (! $a) {
            return null;
        }

        $conf = $a->confirmaciones;
        if (is_string($conf)) {
            $conf = json_decode($conf, true);
        }

        $filas = [];
        foreach ((array) $conf as $k => $v) {
            $filas[] = [
                'etiqueta' => is_string($k) ? ucfirst(str_replace('_', ' ', $k)) : '•',
                'valor' => is_bool($v) ? ($v ? 'Sí' : 'No') : (is_array($v) ? implode(', ', array_map('strval', $v)) : (string) $v),
            ];
        }

        return [
            'fecha' => $a->fecha ? \Illuminate\Support\Carbon::parse($a->fecha)->format('d/m/Y') : null,
            'recibe' => $a->recibe_nombre,
            'cargo' => $a->recibe_cargo,
            'calificacion' => $a->calificacion,
            'observaciones' => $a->observaciones_cliente,
            'confirmaciones' => $filas,
        ];
    }
}
