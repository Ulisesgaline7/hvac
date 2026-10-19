<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Providers\RlsServiceProvider;
use App\Services\FacturaPdf;
use DomainException;
use Illuminate\Support\Facades\Auth;

class FacturaPdfController extends Controller
{
    public function __invoke(int $factura, FacturaPdf $pdf)
    {
        if (! Auth::check()) {
            return redirect()->guest(route('filament.admin.auth.login'));
        }

        abort_unless(Auth::user()->hasAnyRole(['administrador', 'coordinador']), 403);

        // Esta ruta no pasa por el panel de Filament: el contexto RLS se fija aquí.
        RlsServiceProvider::aplicar(Auth::user());

        $factura = Factura::findOrFail($factura);

        try {
            $contenido = $pdf->render($factura);
        } catch (DomainException $e) {
            abort(409, $e->getMessage());
        }

        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $factura->numero . '.pdf"',
        ]);
    }
}
