<?php

namespace App\Http\Controllers;

use App\Models\OrdenTrabajo;
use App\Enums\EstadoOt;
use App\Services\InformeOtService;
use Illuminate\Support\Facades\Auth;

class InformeOtController extends Controller
{
    public function __invoke(int $ot, InformeOtService $servicio)
    {
        // Con RLS, una OT ajena simplemente no se encuentra (404).
        $orden = OrdenTrabajo::findOrFail($ot);

        $usuario = Auth::user();
        $esInterno = $usuario?->hasAnyRole(['administrador', 'coordinador']) ?? false;
        $esTecnico = $usuario?->hasRole('tecnico') ?? false;

        // El cliente solo ve informes de OT cerradas; los borradores no salen del equipo.
        abort_if(! $esInterno && ! $esTecnico && $orden->estado !== EstadoOt::Cerrada, 404);

        return response($servicio->pdf($orden), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="informe-' . $orden->numero . '.pdf"',
        ]);
    }
}
