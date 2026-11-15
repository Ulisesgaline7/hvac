<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Cotizacion;
use App\Models\Factura;
use App\Models\Pago;
use App\Providers\RlsServiceProvider;
use App\Services\ContratoPdf;
use App\Services\CotizacionPdf;
use App\Services\FacturaPdf;
use App\Services\ReciboPdf;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PortalDocumentoController extends Controller
{
    private function cliente(): int
    {
        $u = Auth::user();
        abort_unless($u && $u->hasAnyRole(['cliente_admin', 'cliente_consulta']) && $u->cliente_id, 403);
        RlsServiceProvider::aplicar($u);

        return (int) $u->cliente_id;
    }

    private function pdf(string $contenido, string $nombre)
    {
        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nombre . '.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function cotizacion(int $id, CotizacionPdf $pdf)
    {
        $c = $this->cliente();
        $m = Cotizacion::where('cliente_id', $c)->whereIn('estado', ['enviada', 'aprobada'])->findOrFail($id);

        return $this->pdf($pdf->render($m), $m->numero);
    }

    public function factura(int $id, FacturaPdf $pdf)
    {
        $c = $this->cliente();
        $m = Factura::where('cliente_id', $c)->where('estado', '!=', 'borrador')->findOrFail($id);

        try {
            $contenido = $pdf->render($m);
        } catch (DomainException $e) {
            abort(409, $e->getMessage());
        }

        return $this->pdf($contenido, $m->numero);
    }

    public function recibo(int $id, ReciboPdf $pdf)
    {
        $c = $this->cliente();
        $m = Pago::where('cliente_id', $c)->findOrFail($id);

        return $this->pdf($pdf->render($m), $m->numero);
    }

    public function contrato(int $id, ContratoPdf $pdf)
    {
        $c = $this->cliente();
        $m = Contrato::where('cliente_id', $c)->whereIn('estado', ['activo', 'vencido'])->findOrFail($id);
        $nombre = 'contrato-' . str_pad((string) $m->id, 5, '0', STR_PAD_LEFT);

        // Si ya está firmado se entrega el PDF firmado guardado.
        if ($m->ruta_pdf && Storage::disk('local')->exists($m->ruta_pdf)) {
            return $this->pdf(Storage::disk('local')->get($m->ruta_pdf), $nombre);
        }

        return $this->pdf($pdf->render($m), $nombre);
    }
}
