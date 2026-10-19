<?php

namespace App\Services;

use App\Models\Factura;
use Dompdf\Dompdf;
use Dompdf\Options;
use DomainException;
use Illuminate\Support\Facades\View;

class FacturaPdf
{
    public function html(Factura $f): string
    {
        $prueba = (bool) config('cobros.documentos_prueba');

        // Sin modo de prueba, una factura solo sale con numeración autorizada.
        if (! $prueba && blank($f->cai)) {
            throw new DomainException('No se puede emitir una factura oficial sin CAI. Registra la autorización del SAR o activa cobros.documentos_prueba.');
        }

        $f->loadMissing(['cliente', 'ordenTrabajo', 'pagos', 'cuotas']);

        return View::make('pdf.factura', [
            'f' => $f,
            'prueba' => $prueba,
            'rtn' => $f->rtn_cliente ?: ($f->cliente->rtn ?? null),
            'cuotas' => $f->cuotas->isNotEmpty() ? app(PlanCuotasService::class)->estado($f) : collect(),
        ])->render();
    }

    public function render(Factura $f): string
    {
        $opciones = new Options();
        $opciones->set('isRemoteEnabled', false);
        $opciones->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($opciones);
        $pdf->loadHtml($this->html($f));
        $pdf->setPaper('letter');
        $pdf->render();

        return $pdf->output();
    }
}
