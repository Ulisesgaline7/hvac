<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Factura;
use App\Models\Pago;
use App\Providers\RlsServiceProvider;
use App\Services\CotizacionPdf;
use App\Services\FacturaPdf;
use App\Services\ReciboPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentoPreviaController extends Controller
{
    public function __invoke(Request $request, string $tipo)
    {
        abort_unless(Auth::check() && Auth::user()->hasRole('administrador'), 403);
        RlsServiceProvider::aplicar(Auth::user());

        $id = $request->integer('id');
        $buscar = fn (string $modelo) => $modelo::query()
            ->when($id, fn ($q) => $q->whereKey($id))->latest('id')->first();

        $pdf = match ($tipo) {
            'cotizacion' => ($r = $buscar(Cotizacion::class)) ? app(CotizacionPdf::class)->render($r) : null,
            'recibo' => ($r = $buscar(Pago::class)) ? app(ReciboPdf::class)->render($r) : null,
            'factura' => ($r = $buscar(Factura::class))
                ? (method_exists(FacturaPdf::class, 'render') ? app(FacturaPdf::class)->render($r) : abort(501, 'FacturaPdf no tiene render()'))
                : null,
            default => abort(404),
        };

        abort_if($pdf === null, 404, 'No hay registros para previsualizar.');

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="previa-' . $tipo . '.pdf"',
        ]);
    }
}
