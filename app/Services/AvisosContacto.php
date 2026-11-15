<?php

namespace App\Services;

use App\Models\Contacto;
use Illuminate\Support\Facades\Log;

/** Avisos del formulario de contacto de la web: alerta interna y acuse al visitante. Nunca lanza excepciones. */
final class AvisosContacto
{
    public static function nuevo(Contacto $c): void
    {
        try {
            $vars = [
                'nombre' => (string) $c->nombre,
                'telefono' => (string) $c->telefono,
                'correo' => $c->email ?: '-',
                'ciudad' => $c->ciudad ?: '-',
                'servicio' => $c->servicio ?: '-',
                'mensaje' => (string) $c->mensaje,
            ];

            // Un visitante no tiene sesión: las consultas internas deben correr con contexto interno.
            $interno = fn (callable $f) => class_exists(\App\Providers\RlsServiceProvider::class)
                ? \App\Providers\RlsServiceProvider::comoInterno($f)
                : $f();

            $interno(fn () => NotificadorTecnico::enviar('contacto_web_nuevo', $vars, null, null, null, url('/admin')));

            if (filled($c->email)) {
                NotificadorCorreo::enviar('contacto_recibido', $c->email, $vars, null, 'contacto');
            }
        } catch (\Throwable $e) {
            Log::warning('Avisos de contacto fallaron: ' . $e->getMessage());
        }
    }
}
