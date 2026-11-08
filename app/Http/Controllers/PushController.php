<?php

namespace App\Http\Controllers;

use App\Models\PushSuscripcion;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function clave()
    {
        return response()->json(['clave' => config('webpush.public_key')]);
    }

    public function guardar(Request $r)
    {
        $d = $r->validate([
            'endpoint' => 'required|url|max:2000',
            'keys.p256dh' => 'required|string|max:255',
            'keys.auth' => 'required|string|max:255',
        ]);

        PushSuscripcion::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $d['endpoint'])],
            [
                'user_id' => $r->user()->id,
                'endpoint' => $d['endpoint'],
                'p256dh' => $d['keys']['p256dh'],
                'auth' => $d['keys']['auth'],
                'user_agent' => substr((string) $r->userAgent(), 0, 255),
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function borrar(Request $r)
    {
        $d = $r->validate(['endpoint' => 'required|url|max:2000']);
        PushSuscripcion::where('user_id', $r->user()->id)->where('endpoint_hash', hash('sha256', $d['endpoint']))->delete();

        return response()->json(['ok' => true]);
    }
}
