<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;

class GeneradorEtiquetas
{
    public function __construct(private GeneradorQr $qr)
    {
    }

    public function pdf(Collection $equipos): string
    {
        $equipos = \Illuminate\Database\Eloquent\Collection::make($equipos->all());
        $equipos->loadMissing(['area.sucursal.cliente', 'tipo']);

        $etiquetas = $equipos->map(fn ($equipo) => [
            'equipo' => $equipo,
            'qr' => $this->qr->dataUri($equipo),
        ]);

        $html = View::make('etiquetas.identificacion', [
            'etiquetas' => $etiquetas,
            'ancho' => config('etiquetas.ancho_mm'),
            'alto' => config('etiquetas.alto_mm'),
        ])->render();

        $opciones = new Options();
        $opciones->set('isRemoteEnabled', false);
        $opciones->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($opciones);
        $pdf->loadHtml($html);

        $mm = 72 / 25.4;
        $pdf->setPaper([0, 0, config('etiquetas.ancho_mm') * $mm, config('etiquetas.alto_mm') * $mm]);
        $pdf->render();

        return $pdf->output();
    }
}
