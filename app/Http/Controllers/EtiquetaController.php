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
        $this->autorizar();

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

    private function autorizar(): void
    {
        if (! Auth::check()) {
            abort(redirect()->guest(route('filament.admin.auth.login')));
        }

        abort_unless(Auth::user()->hasAnyRole(['administrador', 'coordinador']), 403);
    }

    private function responder($equipos, string $nombre)
    {
        return response($this->generador->pdf($equipos), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"$nombre\"",
        ]);
    }
}
