<?php

namespace App\Http\Controllers;

use App\Models\WebPagina;

class PaginaWebController
{
    public function show(string $slug)
    {
        $pagina = WebPagina::query()->where('slug', $slug)->where('publicada', true)->firstOrFail();

        return view('web.pagina', ['pagina' => $pagina]);
    }
}
