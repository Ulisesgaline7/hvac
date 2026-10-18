<?php

namespace App\Http\Controllers;

use App\Models\Informe;
use App\Models\OrdenTrabajo;
use App\Services\InformeOtService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class InformeOtController extends Controller
{
    public function __invoke(int $ot, InformeOtService $servicio)
    {
        // Con RLS y el scope por cliente, una OT ajena simplemente no se encuentra (404).
        $orden = OrdenTrabajo::findOrFail($ot);

        $usuario = Auth::user();
        $esPersonal = ($usuario?->hasAnyRole(['administrador', 'coordinador', 'tecnico'])) ?? false;

        $cabeceras = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="informe-' . $orden->numero . '.pdf"',
        ];

        if ($esPersonal) {
            $informe = Informe::where('orden_trabajo_id', $orden->id)->first();

            // Si ya hay uno guardado se sirve ese (el mismo que verá el cliente, con su QR).
            if ($informe?->ruta_pdf && Storage::disk('local')->exists($informe->ruta_pdf)) {
                return response(Storage::disk('local')->get($informe->ruta_pdf), 200, $cabeceras);
            }

            return response($servicio->pdf($orden), 200, $cabeceras);
        }

        // El cliente solo recibe informes publicados por el coordinador.
        $informe = Informe::where('orden_trabajo_id', $orden->id)->where('estado', 'publicado')->first();
        abort_if(! $informe || ! $informe->ruta_pdf || ! Storage::disk('local')->exists($informe->ruta_pdf), 404);

        return response(Storage::disk('local')->get($informe->ruta_pdf), 200, $cabeceras);
    }
}
