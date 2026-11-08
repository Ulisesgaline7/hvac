<?php

namespace App\Services;

use App\Models\ActaConformidad;
use App\Models\Cliente;
use App\Models\Garantia;
use App\Models\OrdenTrabajo;
use App\Models\Sucursal;
use App\Support\FirmaEmpresa;
use Dompdf\Dompdf;

/** Documentos de entrega al cliente, cada uno como PDF independiente. */
class EntregablesOt
{
    public const TIPOS = ['garantia' => 'Certificado de garantía', 'capacitacion' => 'Constancia de capacitación', 'calendario' => 'Calendario de mantenimiento (propuesta)', 'protocolo' => 'Protocolo de puesta en marcha'];

    public function pdf(OrdenTrabajo $ot, string $tipo, array $opciones = []): string
    {
        abort_unless(isset(self::TIPOS[$tipo]), 404);

        $datos = $this->base($ot, $tipo) + match ($tipo) {
            'garantia' => $this->garantia($ot, $opciones),
            'calendario' => $this->calendario($ot, $opciones),
            'protocolo' => $this->protocolo($ot, $opciones),
            default => $this->capacitacion($ot, $opciones),
        };

        $dompdf = new Dompdf(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false]);
        $dompdf->loadHtml(view('pdf.entregable-' . $tipo, $datos)->render());
        $dompdf->setPaper('letter');
        $dompdf->render();

        $pdf = $dompdf->output();

        if (config('firmas.certificar_entregables', true)) {
            $tmp = tempnam(sys_get_temp_dir(), 'ent');
            file_put_contents($tmp, $pdf);
            try {
                app(FirmaPdf::class)->firmar($tmp);
                $pdf = file_get_contents($tmp);
            } finally {
                @unlink($tmp);
            }
        }

        return $pdf;
    }

    private function base(OrdenTrabajo $ot, string $tipo): array
    {
        return [
            'ot' => $ot,
            'titulo' => self::TIPOS[$tipo],
            'cliente' => Cliente::find($ot->cliente_id),
            'sucursal' => Sucursal::find($ot->sucursal_id),
            'generado' => now()->format('d/m/Y H:i'),
            'firma_tecnico' => \App\Providers\RlsServiceProvider::comoInterno(fn () => FirmaEmpresa::deUsuario($ot->tecnico?->user)),
            'firma_supervisor' => \App\Providers\RlsServiceProvider::comoInterno(fn () => FirmaEmpresa::dePrimerRol('supervisor')),
            'firma_administrador' => \App\Providers\RlsServiceProvider::comoInterno(fn () => FirmaEmpresa::dePrimerRol('administrador')),
        ];
    }

    private function garantia(OrdenTrabajo $ot, array $opciones = []): array
    {
        $garantias = Garantia::where('orden_trabajo_id', $ot->id)->when($opciones['linea'] ?? null, fn ($q, $id) => $q->where('orden_trabajo_equipo_id', $id))->with('equipo')->orderBy('id')->get()
            ->map(fn ($g) => [
                'numero' => $g->numero,
                "token" => app(\App\Services\VerificacionDocumento::class)->token($g),
                'equipo' => $g->equipo?->codigo,
                'dias' => $g->dias,
                'categoria' => $g->categoria,
                'desde' => $g->desde?->format('d/m/Y'),
                'hasta' => $g->hasta?->format('d/m/Y'),
                'cubre' => $g->cubre,
                'no_cubre' => $g->no_cubre,
                'quien_corrige' => $g->quien_corrige,
            ])->all();

        return ['garantias' => $garantias, 'firma_cliente' => $this->firmaCliente($ot)];
    }

    private function capacitacion(OrdenTrabajo $ot, array $opciones = []): array
    {
        $equipos = $ot->lineas()->when($opciones['linea'] ?? null, fn ($q, $id) => $q->where('id', $id))->with(['equipo', 'ficha'])->orderBy('id')->get()
            ->map(function ($l) {
                $r = $l->ficha?->respuestas ?? [];

                return [
                    'codigo' => $l->equipo?->codigo ?? '—',
                    'entregado' => array_values((array) ($r['capacitacion_docs'] ?? [])),
                    'capacitado' => (bool) ($r['capacitacion'] ?? false),
                    'copia_digital' => $l->equipo_id
                        ? \App\Providers\RlsServiceProvider::comoInterno(
                            fn () => \Spatie\MediaLibrary\MediaCollections\Models\Media::where('model_type', \App\Models\Equipo::class)
                                ->where('model_id', $l->equipo_id)->where('collection_name', 'documentos_equipo')->exists())
                        : false,
                ];
            })
            ->filter(fn ($e) => $e['entregado'] || $e['capacitado'])
            ->values()->all();

        $acta = ActaConformidad::where('orden_trabajo_id', $ot->id)->latest('id')->first();

        return [
            'equipos' => $equipos,
            'recibe' => $acta?->recibe_nombre,
            'cargo' => $acta?->recibe_cargo,
            'firma_cliente' => $this->firmaCliente($ot),
        ];
    }

    /** Propuesta: visitas cada N meses a partir del cierre (o la última fecha conocida de la OT). */
    private function calendario(OrdenTrabajo $ot, array $opciones): array
    {
        $cada = (int) ($opciones['cada'] ?? 3);
        $cada = in_array($cada, [1, 2, 3, 4, 6, 12], true) ? $cada : 3;
        $visitas = max(1, min(12, (int) ($opciones['visitas'] ?? 4)));

        $base = $ot->cerrada_en ?? $ot->check_out_at ?? $ot->fecha_programada ?? now();
        $base = \Illuminate\Support\Carbon::parse($base)->startOfDay();

        $fechas = [];
        for ($i = 1; $i <= $visitas; $i++) {
            $fechas[] = ['n' => $i, 'fecha' => $base->copy()->addMonthsNoOverflow($cada * $i)->format('d/m/Y')];
        }

        $equipos = $ot->lineas()->with('equipo.tipo')->orderBy('id')->get()
            ->map(fn ($l) => ['codigo' => $l->equipo?->codigo ?? '—', 'tipo' => $l->equipo?->tipo?->nombre])
            ->all();

        return [
            'fechas' => $fechas,
            'equipos' => $equipos,
            'cada' => $cada,
            'desde' => $base->format('d/m/Y'),
            'firma_cliente' => null,
        ];
    }

    /** Protocolo de puesta en marcha: una sección por equipo, con la ficha, el refrigerante y las firmas de la ficha. */
    private function protocolo(OrdenTrabajo $ot, array $opciones = []): array
    {
        $medClaves = ['presion_succion', 'presion_descarga', 'corriente_compresor', 'voltaje_compresor', 'temp_retorno', 'temp_suministro', 'vacio_micrones', 'hermeticidad_presion_nitrogeno', 'hermeticidad_tiempo'];

        $equipos = $ot->lineas()->when($opciones['linea'] ?? null, fn ($q, $id) => $q->where('id', $id))->with(['equipo', 'ficha', 'media'])->orderBy('id')->get()
            ->map(function ($l) use ($medClaves) {
                $ficha = $l->ficha;
                $r = $ficha?->respuestas ?? [];
                $campos = collect($ficha ? $ficha->campos() : [])->keyBy('clave');

                $mediciones = [];
                foreach ($medClaves as $k) {
                    if (! $campos->has($k)) {
                        continue;
                    }
                    $c = $campos[$k];
                    $v = $r[$k] ?? null;
                    $mediciones[] = [
                        'etiqueta' => preg_replace('/^Prueba final · /u', '', $c['etiqueta']),
                        'valor' => ($v === null || $v === '') ? '—' : $v . (empty($c['unidad']) ? '' : ' ' . $c['unidad']),
                    ];
                }

                $ret = $r['temp_retorno'] ?? null;
                $sum = $r['temp_suministro'] ?? null;
                $salto = (is_numeric($ret) && is_numeric($sum)) ? number_format((float) $ret - (float) $sum, 1, '.', '') . ' °C' : null;

                $checklist = $campos->filter(fn ($c) => $c['tipo'] === 'seleccion' && $c['clave'] !== 'tipo_unidad')
                    ->map(fn ($c) => ['etiqueta' => $c['etiqueta'], 'valor' => $r[$c['clave']] ?? '—'])->values()->all();

                $movs = $l->movimientos()->with('cilindro.refrigerante')->orderBy('id')->get();
                $refrigerante = [];
                foreach (['carga' => 'Refrigerante cargado', 'recuperacion' => 'Refrigerante recuperado'] as $tipo => $etiqueta) {
                    $g = $movs->where('tipo', $tipo);
                    if ($g->isEmpty()) {
                        continue;
                    }
                    $gas = $g->map(fn ($m) => $m->cilindro?->refrigerante?->nombre)->filter()->unique()->implode(', ');
                    $refrigerante[] = ['etiqueta' => $etiqueta . ($gas !== '' ? " ($gas)" : ''), 'valor' => number_format((float) $g->sum('cantidad_kg'), 2, '.', '') . ' kg'];
                }

                $img = function ($coleccion) use ($l) {
                    $m = $l->getFirstMedia($coleccion);

                    return ($m && is_file($m->getPath())) ? 'data:image/png;base64,' . base64_encode(file_get_contents($m->getPath())) : null;
                };

                return [
                    'codigo' => $l->equipo?->codigo ?? '—',
                    'tiene_ficha' => (bool) $ficha,
                    'mediciones' => $mediciones,
                    'salto' => $salto,
                    'checklist' => $checklist,
                    'refrigerante' => $refrigerante,
                    'pruebas' => $r['pruebas_finales'] ?? null,
                    'firma_tecnico' => $img('firma_tecnico'),
                    'firma_cliente' => $img('firma_cliente'),
                    'fecha_ficha' => $ficha?->updated_at?->format('d/m/Y H:i'),
                ];
            })->all();

        $acta = ActaConformidad::where('orden_trabajo_id', $ot->id)->latest('id')->first();

        return ['equipos' => $equipos, 'recibe' => $acta?->recibe_nombre, 'cargo' => $acta?->recibe_cargo, 'firma_cliente' => null];
    }

    private function firmaCliente(OrdenTrabajo $ot): ?string
    {
        $acta = ActaConformidad::where('orden_trabajo_id', $ot->id)->latest('id')->first();
        $m = $acta?->getFirstMedia('firma_cliente')
            ?? $ot->lineas()->get()->map(fn ($l) => $l->getFirstMedia('firma_cliente'))->filter()->first();

        return ($m && is_file($m->getPath()))
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($m->getPath()))
            : null;
    }
}
