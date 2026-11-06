<?php

namespace App\Services;

use App\Models\Contrato;
use App\Support\DocumentoMarca;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;

class ContratoPdf
{
    /** $firmaPng: bytes del PNG de la firma del cliente; $firmante: nombre, cargo, fecha, ip. */
    public function render(Contrato $c, ?string $firmaPng = null, ?array $firmante = null): string
    {
        $c->loadMissing(['cliente', 'equipos.tipo']);

        $html = View::make('pdf.contrato', [
            'c' => $c,
            'm' => DocumentoMarca::todo(),
            'firma' => $firmaPng ? 'data:image/png;base64,' . base64_encode($firmaPng) : null,
            'firmante' => $firmante,
            'rep' => config('contrato.representante'),
            'repFirma' => \Illuminate\Support\Facades\Storage::disk('local')->exists('firmas/representante.png')
                ? 'data:image/png;base64,' . base64_encode(\Illuminate\Support\Facades\Storage::disk('local')->get('firmas/representante.png'))
                : null,
        ])->render();

        $opciones = new Options();
        $opciones->set('isRemoteEnabled', false);
        $opciones->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($opciones);
        $pdf->loadHtml($html);
        $pdf->setPaper('letter');
        $pdf->render();

        return $pdf->output();
    }
}
