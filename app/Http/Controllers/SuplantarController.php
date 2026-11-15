<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Dominios;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class SuplantarController extends Controller
{
    public function iniciar(Request $request, User $user)
    {
        $admin = $request->user();
        abort_unless($admin->hasRole('administrador'), 403);
        abort_if($request->session()->has('suplantador_id'), 403);
        abort_if($user->id === $admin->id, 403);
        abort_if($user->hasRole('administrador'), 403);
        activity()->causedBy($admin)->performedOn($user)->log('Inicio suplantacion');

        $panel = Dominios::panelDe($user);
        abort_if(! $panel, 403);
        $dominio = Dominios::de($panel);

        // Con subdominios, la sesión del usuario se abre en su propio panel y la del administrador no se toca.
        if ($dominio && $request->getHost() !== $dominio) {
            URL::forceRootUrl('https://' . $dominio);
            $url = URL::temporarySignedRoute('sso.entrar', now()->addMinute(), [
                'user' => $user->id,
                'panel' => $panel,
                'jti' => Str::random(24),
                'suplantador' => $admin->id,
            ]);
            URL::forceRootUrl(null);

            return redirect()->away($url);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('password_hash_web');
        $request->session()->put('suplantador_id', $admin->id);

        return redirect(Filament::getPanel($panel)->getUrl());
    }

    public function volver(Request $request)
    {
        $id = $request->session()->get('suplantador_id');
        abort_unless($id, 403);
        $admin = User::findOrFail($id);
        abort_unless($admin->hasRole('administrador'), 403);
        $suplantado = $request->user();
        activity()->causedBy($admin)->performedOn($suplantado)->log('Fin suplantacion');

        $dominioAdmin = Dominios::de('admin');
        if ($dominioAdmin && $request->getHost() !== $dominioAdmin) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->away('https://' . $dominioAdmin . '/');
        }

        Auth::login($admin);
        $request->session()->forget(['suplantador_id', 'password_hash_web']);
        $request->session()->regenerate();

        return redirect(Filament::getPanel('admin')->getUrl());
    }
}
