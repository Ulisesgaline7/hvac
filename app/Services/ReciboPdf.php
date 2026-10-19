<?php

namespace App\Services;

use App\Models\Pago;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;

class ReciboPdf
{
    /** Un anticipo se entrega como comprobante de depósito (no es factura); un pago, como recibo. */
    public function titulo(Pago $p): string
    {
        return $p->tipo === 'anticipo' ? 'Comprobante de depósito' : 'Recibo de pago';
    }

    public function html(Pago $p): string
    {
        $p->loadMissing(['cliente', 'ordenTrabajo', 'factura']);

        $acumulado = $p->orden_trabajo_id
            ? (float) Pago::where('orden_trabajo_id', $p->orden_trabajo_id)->where('tipo', 'anticipo')->sum('monto')
            : null;

        return View::make('pdf.recibo', [
            'pago' => $p,
            'titulo' => $this->titulo($p),
            'acumulado' => $p->tipo === 'anticipo' ? $acumulado : null,
            'prueba' => (bool) config('cobros.documentos_prueba'),
        ])->render();
    }

    public function render(Pago $p): string
    {
        $opciones = new Options();
        $opciones->set('isRemoteEnabled', false);
        $opciones->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($opciones);
        $pdf->loadHtml($this->html($p));
        $pdf->setPaper('letter');
        $pdf->render();

        return $pdf->output();
    }
}
