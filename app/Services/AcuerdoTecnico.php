<?php

namespace App\Services;

use App\Models\AceptacionTecnico;
use App\Models\DocumentoCatalogo;
use App\Models\Tecnico;
use DomainException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AcuerdoTecnico
{
    public const CODIGO = 'DOC-007';

    private const ETIQUETAS = '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><h4><table><thead><tbody><tr><th><td><blockquote><hr>';

    /** El acuerdo solo rige cuando está vigente y con texto. */
    public function documento(): ?DocumentoCatalogo
    {
        $d = DocumentoCatalogo::where('codigo', self::CODIGO)->first();

        return ($d && $d->estado === 'vigente' && filled($d->contenido)) ? $d : null;
    }

    public function hash(DocumentoCatalogo $d): string
    {
        return hash('sha256', (string) $d->contenido);
    }

    public function textoSeguro(DocumentoCatalogo $d): string
    {
        return strip_tags((string) $d->contenido, self::ETIQUETAS);
    }

    /** Devuelve el documento si el técnico aún debe aceptarlo; null si no hay acuerdo vigente o ya aceptó esta versión. */
    public function pendiente(Tecnico $t): ?DocumentoCatalogo
    {
        $d = $this->documento();
        if (! $d) {
            return null;
        }

        $ya = AceptacionTecnico::where('tecnico_id', $t->id)
            ->where('documento_id', $d->id)
            ->where('hash_texto', $this->hash($d))
            ->exists();

        return $ya ? null : $d;
    }

    public function aceptar(Tecnico $t, string $nombre, string $firma, ?string $ip, ?string $userAgent): AceptacionTecnico
    {
        $d = $this->pendiente($t);
        if (! $d) {
            throw new DomainException('No hay un acuerdo pendiente de aceptar.');
        }

        if (! str_starts_with($firma, 'data:image/png;base64,') || strlen($firma) > 2_000_000) {
            throw new DomainException('La firma no es válida.');
        }
        $png = base64_decode(substr($firma, strlen('data:image/png;base64,')), true);
        if ($png === false || ! str_starts_with($png, "\x89PNG")) {
            throw new DomainException('La firma no es válida.');
        }

        $ruta = "tecnicos/acuerdos/{$t->id}-{$d->id}-" . Str::random(12) . '.png';
        $disco = Storage::disk('local');
        $disco->put($ruta, $png);

        try {
            return AceptacionTecnico::create([
                'tecnico_id' => $t->id,
                'documento_id' => $d->id,
                'version' => $d->version,
                'hash_texto' => $this->hash($d),
                'firmado_nombre' => $nombre,
                'aceptado_at' => now(),
                'ip' => $ip,
                'user_agent' => $userAgent,
                'ruta_firma' => $ruta,
            ]);
        } catch (\Throwable $e) {
            $disco->delete($ruta);
            throw new DomainException('No se pudo registrar la aceptación. Intente de nuevo.');
        }
    }

    /** Lanza si el usuario es técnico y tiene el acuerdo pendiente. Admin y coordinador no se bloquean. */
    public function exigirParaUsuario($usuario): void
    {
        if (! $usuario || $usuario->hasAnyRole(['administrador', 'coordinador'])) {
            return;
        }

        $t = Tecnico::where('user_id', $usuario->getAuthIdentifier())->first();
        if ($t && ($d = $this->pendiente($t))) {
            throw new DomainException("Antes de iniciar debe aceptar y firmar «{$d->nombre}».");
        }
    }
}
