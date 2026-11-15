<?php

namespace App\Http\Controllers;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/** Abre sesión en el subdominio de un panel mediante un enlace firmado de un solo uso (dura 1 minuto). */
class SsoController extends Controller
{
    public function entrar(Request $request, User $user)
    {
        $panel = Filament::getPanel((string) $request->query('panel'));

        abort_unless($user->canAccessPanel($panel), 403);
        abort_unless(Cache::add('sso:' . $request->query('jti'), 1, now()->addMinutes(2)), 403);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('password_hash_web');
        if ($request->query('suplantador')) {
            $request->session()->put('suplantador_id', (int) $request->query('suplantador'));
        }

        return redirect($panel->getUrl());
    }
}
