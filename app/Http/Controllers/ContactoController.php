<?php

namespace App\Http\Controllers;

use App\Models\Contacto;
use Illuminate\Http\Request;

class ContactoController extends Controller
{
    public function store(Request $request)
    {
        if ($request->filled('sitio_web')) {
            return back();
        }

        $datos = $request->validate([
            'nombre'   => 'required|string|max:120',
            'telefono' => 'required|string|max:30',
            'email'    => 'nullable|email|max:150',
            'ciudad'   => 'nullable|string|max:80',
            'servicio' => 'nullable|string|max:80',
            'mensaje'  => 'required|string|max:2000',
        ]);

        Contacto::create($datos + ['ip' => $request->ip()]);

        return redirect(url('/') . '#contacto')
            ->with('ok', 'Gracias. Recibimos tu mensaje y te contactaremos pronto.');
    }
}
