<?php

namespace App\Support;

/** Normaliza móviles de Honduras al formato que usa WhatsApp: 504 + 8 dígitos, sin "+". Devuelve null si no es válido. */
final class TelefonoHn
{
    public static function normalizar(?string $crudo): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $crudo);

        if (strlen($d) === 11 && str_starts_with($d, '504')) {
            $d = substr($d, 3);
        }

        // Móviles hondureños: 8 dígitos que empiezan con 3, 8 o 9.
        if (strlen($d) !== 8 || ! preg_match('/^[389]/', $d)) {
            return null;
        }

        return '504' . $d;
    }
}
