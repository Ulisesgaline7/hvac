<?php

namespace App\Http\Controllers;

use App\Models\OrdenTrabajo;
use App\Services\InformeOtService;

class InformeOtController extends Controller
{
    public function __invoke(int $ot, InformeOtService $servicio)
    {
        // Con RLS, una OT ajena simplemente no se encuentra (404).
        $orden = OrdenTrabajo::findOrFail($ot);

        return response($servicio->pdf($orden), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="informe-' . $orden->numero . '.pdf"',
        ]);
    }
}
