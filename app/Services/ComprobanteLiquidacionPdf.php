<?php

namespace App\Services;

use App\Models\LiquidacionTecnico;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;

class ComprobanteLiquidacionPdf
{
    public function html(LiquidacionTecnico $l): string
    {
        $l->loadMissing(['tecnico.user', 'pagos.ordenTrabajo.cliente']);

        return View::make('pdf.comprobante-liquidacion', [
            'liq' => $l,
            'tecnico' => $l->tecnico,
            'pagos' => $l->pagos->sortBy('orden_trabajo_id'),
            'prueba' => (bool) config('cobros.documentos_prueba'),
        ])->render();
    }

    public function render(LiquidacionTecnico $l): string
    {
        $opciones = new Options();
        $opciones->set('isRemoteEnabled', false);
        $opciones->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($opciones);
        $pdf->loadHtml($this->html($l));
        $pdf->setPaper('letter');
        $pdf->render();

        return $pdf->output();
    }
}
