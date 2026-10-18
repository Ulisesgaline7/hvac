<?php

namespace App\Http\Middleware;

use App\Providers\RlsServiceProvider;
use Closure;
use Illuminate\Http\Request;

class ReiniciarContextoRls
{
    public function handle(Request $request, Closure $next)
    {
        RlsServiceProvider::limpiar();

        return $next($request);
    }
}
