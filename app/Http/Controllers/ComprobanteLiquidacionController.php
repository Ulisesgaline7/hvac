<?php

namespace App\Http\Controllers;

use App\Models\LiquidacionTecnico;
use App\Models\Tecnico;
use App\Providers\RlsServiceProvider;
use App\Services\ComprobanteLiquidacionPdf;
use Illuminate\Support\Facades\Auth;

class ComprobanteLiquidacionController extends Controller
{
    public function __invoke(int $liquidacion, ComprobanteLiquidacionPdf $pdf)
    {
        if (! Auth::check()) {
            return redirect()->guest(route('filament.admin.auth.login'));
        }

        $u = Auth::user();
        $esPersonal = $u->hasAnyRole(['administrador', 'coordinador']);
        abort_unless($esPersonal || $u->hasRole('tecnico'), 403);

        RlsServiceProvider::aplicar($u);

        $liq = LiquidacionTecnico::findOrFail($liquidacion);

        // Sin número no hay comprobante: un borrador no se imprime.
        abort_if(blank($liq->numero), 404);

        if (! $esPersonal) {
            $miId = Tecnico::where('user_id', $u->id)->value('id');
            abort_unless($miId && (int) $liq->tecnico_id === (int) $miId && $liq->estado === 'pagada', 403);
        }

        return response($pdf->render($liq), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $liq->numero . '.pdf"',
        ]);
    }
}
