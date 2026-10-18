<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EquipoQrController extends Controller
{
    public function __invoke(Request $request, string $token)
    {
        if (! Auth::check()) {
            return redirect()->guest(route('filament.admin.auth.login'));
        }

        $usuario = Auth::user();

        $equipo = Equipo::with(['area.sucursal.cliente', 'tipo', 'marca', 'refrigerante'])
            ->where('token_qr', $token)
            ->first();

        $puedeVer = $equipo && (
            $usuario->hasAnyRole(['administrador', 'coordinador'])
            || ($usuario->cliente_id && $usuario->hasAnyRole(['cliente_admin', 'cliente_consulta']))
        );

        if (! $puedeVer) {
            return response()->view('equipo.generica', [], 404);
        }

        return view('equipo.publica', ['equipo' => $equipo]);
    }
}
