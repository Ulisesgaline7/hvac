<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/** Firma y sello de la empresa para los PDF, como data URI (Dompdf no carga imágenes remotas). */
final class FirmaEmpresa
{
    private static array $memoria = [];

    public static function activa(string $documento): bool
    {
        return (bool) config("firmas.documentos.$documento", false);
    }

    public static function firma(): ?string
    {
        return self::imagen('firmas/representante.png');
    }

    public static function sello(): ?string
    {
        return self::imagen('firmas/sello.png');
    }

    public static function firmantes(string $documento): array
    {
        $v = config("firmas.documentos.$documento", []);

        return is_array($v) ? $v : [];
    }

    public static function deUsuario(?\App\Models\User $u): ?string
    {
        return $u?->firma_ruta ? self::imagen($u->firma_ruta) : null;
    }

    /** Primer usuario con ese rol y con firma cargada. */
    public static function dePrimerRol(string $rol): ?string
    {
        return self::deUsuario(\App\Models\User::role($rol)->whereNotNull('firma_ruta')->orderBy('id')->first());
    }

    private static function imagen(string $ruta): ?string
    {
        return self::$memoria[$ruta] ??= Storage::disk('local')->exists($ruta)
            ? 'data:image/png;base64,' . base64_encode(Storage::disk('local')->get($ruta))
            : null;
    }
}
