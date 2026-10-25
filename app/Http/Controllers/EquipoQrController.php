<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\OrdenTrabajo;
use App\Models\Tecnico;
use App\Providers\RlsServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EquipoQrController extends Controller
{
    public function __invoke(Request $request, string $token)
    {
        if (! Auth::check()) {
            return redirect()->guest(route('equipo.ingreso'));
        }

        $usuario = Auth::user();

        if ($usuario->hasAnyRole(['administrador', 'coordinador'])
            || ($usuario->cliente_id && $usuario->hasAnyRole(['cliente_admin', 'cliente_consulta']))) {
            $equipo = Equipo::with(['area.sucursal.cliente', 'tipo', 'marca', 'refrigerante'])->where('token_qr', $token)->first();

            return $equipo
                ? view('equipo.publica', ['equipo' => $equipo])
                : response()->view('equipo.generica', [], 404);
        }

        if ($usuario->hasRole('tecnico')) {
            return $this->comoTecnico($usuario->id, $token);
        }

        return response()->view('equipo.generica', [], 404);
    }

    private function comoTecnico(int $userId, string $token)
    {
        // Se consulta como interno para poder mostrar la ficha mínima de equipos ajenos;
        // los datos del cliente solo se cargan si el equipo está en una OT del técnico.
        $datos = RlsServiceProvider::comoInterno(function () use ($userId, $token) {
            $equipo = Equipo::with(['tipo', 'marca'])->where('token_qr', $token)->first();
            if (! $equipo) {
                return null;
            }

            $tecnico = Tecnico::where('user_id', $userId)->first();
            $ordenes = collect();

            if ($tecnico) {
                $ordenes = OrdenTrabajo::query()
                    ->whereHas('lineas', fn ($q) => $q->where('equipo_id', $equipo->id))
                    ->where(fn ($q) => $q->where('tecnico_id', $tecnico->id)
                        ->orWhereHas('cuadrilla', fn ($c) => $c->where('tecnicos.id', $tecnico->id)))
                    ->latest('id')->limit(10)->get()
                    ->map(function (OrdenTrabajo $ot) use ($tecnico) {
                        $rol = $ot->tecnico_id === $tecnico->id
                            ? 'lider'
                            : ($ot->cuadrilla()->where('tecnicos.id', $tecnico->id)->first()?->pivot->rol ?? 'tecnico');

                        return [
                            'numero' => $ot->numero,
                            'tipo' => str_replace('_', ' ', $ot->tipo->value),
                            'estado' => $ot->estado->getLabel(),
                            'fecha' => $ot->fecha_programada?->format('d/m/Y'),
                            'rol' => ucfirst($rol),
                        ];
                    });
            }

            $completa = $ordenes->isNotEmpty();
            if ($completa) {
                $equipo->load(['area.sucursal.cliente', 'refrigerante']);
            }

            return compact('equipo', 'ordenes', 'completa');
        });

        if (! $datos) {
            return response()->view('equipo.generica', [], 404);
        }

        return view('equipo.tecnico', $datos);
    }
}
