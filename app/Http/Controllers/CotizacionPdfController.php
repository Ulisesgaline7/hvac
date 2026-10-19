<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Providers\RlsServiceProvider;
use App\Services\CotizacionPdf;
use Illuminate\Support\Facades\Auth;

class CotizacionPdfController extends Controller
{
    public function __invoke(int $cotizacion, CotizacionPdf $pdf)
    {
        if (! Auth::check()) {
            return redirect()->guest(route('filament.admin.auth.login'));
        }

        abort_unless(Auth::user()->hasAnyRole(['administrador', 'coordinador']), 403);

        // Esta ruta no pasa por el panel de Filament, así que el contexto RLS
        // se fija aquí, después de validar el rol y antes de consultar.
        RlsServiceProvider::aplicar(Auth::user());

        $cotizacion = Cotizacion::findOrFail($cotizacion);

        return response($pdf->render($cotizacion), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $cotizacion->numero . '.pdf"',
        ]);
    }
}
