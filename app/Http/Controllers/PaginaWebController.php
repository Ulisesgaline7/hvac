<?php

namespace App\Http\Controllers;

use App\Models\WebPagina;
use Filament\Facades\Filament;
use Throwable;

class PaginaWebController
{
    public function show(string $slug)
    {
        $pagina = WebPagina::query()->where('slug', $slug)->firstOrFail();

        if (! $pagina->publicada) {
            // Vista previa: solo para quien entra al panel de administración.
            abort_unless($this->puedeVerBorradores(), 404);
        } elseif ($pagina->es_inicio) {
            return redirect('/', 301);
        }

        return view('web.pagina', ['pagina' => $pagina]);
    }

    private function puedeVerBorradores(): bool
    {
        $u = auth()->user();
        if (! $u || ! method_exists($u, 'canAccessPanel')) {
            return false;
        }

        try {
            return (bool) $u->canAccessPanel(Filament::getPanel('admin'));
        } catch (Throwable) {
            return false;
        }
    }
}
