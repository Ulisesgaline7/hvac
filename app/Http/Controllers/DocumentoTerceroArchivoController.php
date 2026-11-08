<?php

namespace App\Http\Controllers;

use App\Models\DocumentoTercero;
use App\Providers\RlsServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentoTerceroArchivoController extends Controller
{
    public function __invoke(int $documento)
    {
        if (! Auth::check()) {
            return redirect()->guest(route('filament.admin.auth.login'));
        }

        abort_unless(Auth::user()->hasAnyRole(['administrador', 'coordinador', 'supervisor', 'tecnico']), 403);

        // Esta ruta no pasa por el panel de Filament: el contexto RLS se fija aquí.
        RlsServiceProvider::aplicar(Auth::user());

        $d = DocumentoTercero::findOrFail($documento);
        $disco = Storage::disk('local');
        abort_unless($d->archivo_ruta && $disco->exists($d->archivo_ruta), 404);

        return $disco->response($d->archivo_ruta, $d->archivo_nombre ?: basename($d->archivo_ruta));
    }
}
