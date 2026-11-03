<?php

namespace App\Services;

use App\Models\DocumentoCatalogo;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;

class DocumentoPdf
{
    private const ETIQUETAS_PERMITIDAS = '<p><br><strong><b><em><i><u><s><ul><ol><li><h1><h2><h3><h4><table><thead><tbody><tr><th><td><blockquote><hr><a>';

    public function render(DocumentoCatalogo $d): string
    {
        $html = View::make('pdf.documento', [
            'd' => $d,
            'contenido' => strip_tags((string) $d->contenido, self::ETIQUETAS_PERMITIDAS),
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
