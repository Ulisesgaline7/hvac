<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForzarCambioPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $u = $request->user();

        if ($u && $u->debe_cambiar_password
            && ! $request->routeIs('filament.*.auth.*')
            && ! $request->hasHeader('X-Livewire')
            && ! $request->is('livewire*')) {
            return redirect(filament()->getProfileUrl());
        }

        return $next($request);
    }
}
