<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;

class CotizacionPdf
{
    public function render(Cotizacion $c): string
    {
        $c->loadMissing(['cliente', 'sucursal', 'lineas']);

        $html = View::make('cotizaciones.pdf', [
            'c' => $c,
            'vendedor' => $c->vendedor_id ? User::find($c->vendedor_id)?->name : null,
            'empresa' => config('cotizacion.empresa'),
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
