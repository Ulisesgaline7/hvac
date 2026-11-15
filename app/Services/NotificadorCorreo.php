<?php

namespace App\Services;

use App\Models\PlantillaNotificacion;
use App\Notifications\CorreoPlantilla;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/** Envía una plantilla por correo a una dirección externa (cliente, visitante de la web). Nunca lanza excepciones. */
final class NotificadorCorreo
{
    /**
     * @param ?string $dedupe Si se da, no repite el envío a esa dirección durante 24 h con la misma clave.
     */
    public static function enviar(string $clave, ?string $correo, array $vars, ?string $url = null, ?string $dedupe = null, array $adjuntos = [], ?string $textoBoton = null): bool
    {
        try {
            $correo = strtolower(trim((string) $correo));
            if (! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                return false;
            }

            $p = PlantillaNotificacion::where('clave', $clave)->where('activa', true)->first();
            if (! $p || ! $p->correo_activo) {
                return false;
            }

            if ($dedupe !== null && ! Cache::add("correo:{$dedupe}:" . sha1($correo), 1, now()->addDay())) {
                return false;
            }

            $map = [];
            foreach ($vars as $k => $v) {
                $map['{' . $k . '}'] = (string) $v;
            }
            $r = fn (string $s) => strtr($s, $map);

            Notification::route('mail', $correo)->notify(
                new CorreoPlantilla($r($p->titulo), $r($p->cuerpo), $url, isset($vars['nombre']) ? (string) $vars['nombre'] : null, $textoBoton, $adjuntos)
            );

            return true;
        } catch (\Throwable $e) {
            Log::warning("Correo {$clave} falló: " . $e->getMessage());

            return false;
        }
    }
}
