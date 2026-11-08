<?php

namespace App\Http\Controllers;

use App\Enums\EstadoOt;
use App\Models\OrdenTrabajo;
use App\Services\EntregablesOt;
use Illuminate\Http\Request;

class EntregableOtController extends Controller
{
    public function __invoke(Request $request, int $ot, string $tipo, EntregablesOt $servicio)
    {
        // Con RLS y el scope por cliente, una OT ajena no se encuentra (404).
        $orden = OrdenTrabajo::findOrFail($ot);

        abort_unless(in_array($orden->estado, [EstadoOt::CompletadaTecnico, EstadoOt::EnRevision, EstadoOt::Cerrada], true), 403,
            'Los documentos de entrega se generan cuando la OT está completada.');

        return response($servicio->pdf($orden, $tipo, $request->only(['cada', 'visitas', 'linea'])), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $tipo . '-' . $orden->numero . '.pdf"',
        ]);
    }
}
