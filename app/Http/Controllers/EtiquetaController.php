<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Services\GeneradorEtiquetas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EtiquetaController extends Controller
{
    public function __construct(private GeneradorEtiquetas $generador)
    {
    }

    public function equipo(Equipo $equipo)
    {
        $this->autorizar($equipo);

        return $this->responder(collect([$equipo]), "etiqueta-{$equipo->codigo}.pdf");
    }

    public function lote(Request $request)
    {
        $this->autorizar();

        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->take(config('etiquetas.maximo_por_lote'));

        abort_if($ids->isEmpty(), 422, 'No se indicaron equipos.');

        $equipos = Equipo::whereIn('id', $ids)->orderBy('codigo')->get();
        abort_if($equipos->isEmpty(), 404);

        return $this->responder($equipos, 'etiquetas-lote.pdf');
    }

    private function autorizar(?Equipo $equipo = null): void
    {
        if (! Auth::check()) {
            abort(redirect()->guest(route('equipo.ingreso')));
        }

        $usuario = Auth::user();

        if ($usuario->hasAnyRole(['administrador', 'coordinador'])) {
            return;
        }

        // El técnico solo imprime etiquetas de equipos que están en una OT de su cuadrilla.
        abort_unless($equipo && $usuario->hasRole('tecnico') && $this->esDeSusOt($usuario->id, $equipo), 403);
    }

    private function esDeSusOt(int $userId, Equipo $equipo): bool
    {
        $tecnicoId = \App\Models\Tecnico::where('user_id', $userId)->value('id');

        return $tecnicoId && \Illuminate\Support\Facades\DB::table('orden_trabajo_equipo')
            ->where('equipo_id', $equipo->id)
            ->whereIn('orden_trabajo_id', \Illuminate\Support\Facades\DB::table('orden_trabajo_tecnico')
                ->where('tecnico_id', $tecnicoId)->select('orden_trabajo_id'))
            ->exists();
    }

    private function responder($equipos, string $nombre)
    {
        return response($this->generador->pdf($equipos), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"$nombre\"",
        ]);
    }
}
