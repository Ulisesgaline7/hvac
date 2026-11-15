<?php

namespace App\Http\Middleware;

use App\Services\DivisionActiva;
use Closure;
use Illuminate\Http\Request;

class DivisionActivaAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $id = (int) $request->session()->get('division_id', 1);
        DivisionActiva::fijar($id > 0 ? $id : 1);

        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        DivisionActiva::fijar(null);
    }
}
