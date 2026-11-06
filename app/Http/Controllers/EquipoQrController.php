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
            $equipo = RlsServiceProvider::comoInterno(
                fn () => Equipo::with(['tipo', 'marca'])->where('token_qr', $token)->first()
            );

            return $equipo
                ? view('equipo.escaneo', ['equipo' => $equipo])
                : response()->view('equipo.generica', [], 404);
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

    public function cambiarEstado(Request $request, string $token)
    {
        $usuario = Auth::user();
        abort_unless($usuario && $usuario->hasAnyRole(['administrador', 'coordinador']), 403);

        $datos = $request->validate([
            'estado' => ['required', \Illuminate\Validation\Rule::enum(\App\Enums\EstadoEquipo::class)],
        ]);
        $nuevo = \App\Enums\EstadoEquipo::from($datos['estado']);

        $equipo = Equipo::where('token_qr', $token)->firstOrFail();

        // La baja definitiva se hace desde el panel, no con un toque en el celular.
        abort_if($nuevo === \App\Enums\EstadoEquipo::DadoDeBaja || $equipo->estado === \App\Enums\EstadoEquipo::DadoDeBaja, 422, 'Este cambio de estado no se permite desde el QR.');

        if ($equipo->estado !== $nuevo) {
            $equipo->update(['estado' => $nuevo]); // Auditable registra usuario y valores
        }

        return redirect()->route('equipo.qr', $token)
            ->with('ok', 'Estado actualizado: ' . str_replace('_', ' ', $nuevo->value));
    }

    /** Sirve una foto de una ficha del equipo, solo a quien puede ver el equipo. */
    public function foto(Request $request, string $token, int $media)
    {
        $usuario = Auth::user();
        abort_unless($usuario, 403);

        $equipo = Equipo::where('token_qr', $token)->first();

        if (! $equipo && $usuario->hasRole('tecnico')) {
            abort(404);
        }
        abort_unless($equipo, 404);

        if ($usuario->hasRole('tecnico') && ! $usuario->hasAnyRole(['administrador', 'coordinador'])) {
            $tecnicoId = Tecnico::where('user_id', $usuario->id)->value('id');
            $permitido = $tecnicoId && \Illuminate\Support\Facades\DB::table('orden_trabajo_equipo')
                ->where('equipo_id', $equipo->id)
                ->whereIn('orden_trabajo_id', \Illuminate\Support\Facades\DB::table('orden_trabajo_tecnico')
                    ->where('tecnico_id', $tecnicoId)->select('orden_trabajo_id'))
                ->exists();
            abort_unless($permitido, 403);
        }

        $m = \Spatie\MediaLibrary\MediaCollections\Models\Media::query()
            ->where('id', $media)
            ->where('model_type', \App\Models\OrdenTrabajoEquipo::class)
            ->whereIn('model_id', \Illuminate\Support\Facades\DB::table('orden_trabajo_equipo')
                ->where('equipo_id', $equipo->id)->select('id'))
            ->where('collection_name', 'like', '%foto%')
            ->where('collection_name', 'not like', '%firma%')
            ->firstOrFail();

        $usarConv = $m->hasGeneratedConversion('informe');
        $ruta = $usarConv ? $m->getPath('informe') : $m->getPath();
        abort_unless(is_file($ruta), 404);

        return response()->file($ruta, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
