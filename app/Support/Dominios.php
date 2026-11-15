<?php

namespace App\Support;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

class Dominios
{
    public static function de(string $panel): ?string
    {
        return config('dominios.' . $panel) ?: null;
    }

    /** Panel al que pertenece la petición, por subdominio o, si no hay, por la ruta. */
    public static function panelPara(Request $request): string
    {
        foreach (['portal', 'tecnico', 'admin'] as $panel) {
            $dominio = self::de($panel);
            if ($dominio && $request->getHost() === $dominio) {
                return $panel;
            }
        }
        if ($request->is('portal', 'portal/*')) {
            return 'portal';
        }
        if ($request->is('tecnico', 'tecnico/*')) {
            return 'tecnico';
        }

        return 'admin';
    }

    public static function urlLogin(string $panel): string
    {
        return route('filament.' . $panel . '.auth.login');
    }

    /** Primer panel al que el usuario puede entrar, o null. */
    public static function panelDe(User $user): ?string
    {
        foreach (['admin', 'tecnico', 'portal'] as $panel) {
            if ($user->canAccessPanel(Filament::getPanel($panel))) {
                return $panel;
            }
        }

        return null;
    }
}
