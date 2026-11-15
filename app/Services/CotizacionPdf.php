<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class CotizacionPdf
{
    /**
     * Borrador: se genera al vuelo. Enviada o aprobada: se congela una vez por estado
     * (con QR y hash) y siempre se sirve ese mismo archivo.
     */
    public function render(Cotizacion $c): string
    {
        if ($c->estado === 'borrador') {
            return $this->generar($c, null, null);
        }

        $disco = Storage::disk('local');
        if ($c->ruta_pdf && $c->pdf_estado === $c->estado && $disco->exists($c->ruta_pdf)) {
            return $disco->get($c->ruta_pdf);
        }

        return $this->emitir($c);
    }

    private function emitir(Cotizacion $c): string
    {
        try {
            $verificacion = app(VerificacionDocumento::class);
            $url = $verificacion->url($c);
            $bytes = $this->generar($c, $verificacion->qr($c), $url);

            $ruta = 'cotizaciones/' . $c->id . '-' . $c->estado . '-' . now()->format('YmdHis') . '.pdf';
            Storage::disk('local')->put($ruta, $bytes);

            $c->timestamps = false;
            try {
                $c->forceFill([
                    'ruta_pdf' => $ruta,
                    'hash_pdf' => hash('sha256', $bytes),
                    'pdf_estado' => $c->estado,
                    'emitida_en' => now(),
                ])->saveQuietly();
            } finally {
                $c->timestamps = true;
            }

            return $bytes;
        } catch (\Throwable $e) {
            report($e);

            return $this->generar($c, null, null);
        }
    }

    private function generar(Cotizacion $c, ?string $qr, ?string $url): string
    {
        $c->loadMissing(['cliente', 'sucursal', 'lineas']);

        $tot = round((float) $c->total, 2);
        $ent = (int) floor($tot);
        $cent = (int) round(($tot - $ent) * 100);
        $letras = ucfirst((new \NumberFormatter('es', \NumberFormatter::SPELLOUT))->format($ent)) . ' lempiras con ' . str_pad((string) $cent, 2, '0', STR_PAD_LEFT) . ' centavos';

        $html = View::make('cotizaciones.pdf', [
            'c' => $c,
            'letras' => $letras,
            'vendedor' => $c->vendedor_id ? User::find($c->vendedor_id)?->name : null,
            'empresa' => config('cotizacion.empresa'),
            'qr' => $qr,
            'verificacion_url' => $url,
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
