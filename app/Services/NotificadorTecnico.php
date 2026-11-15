<?php

namespace App\Services;

use App\Models\PlantillaNotificacion;
use App\Models\User;
use App\Notifications\PlantillaAviso;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/** Busca la plantilla, reemplaza {variables} y avisa al técnico y/o a los roles configurados. Nunca lanza excepciones. */
final class NotificadorTecnico
{
    /**
     * @param ?string $dedupeRoles Si se da, los roles internos reciben el aviso una sola vez por esa clave (5 min).
     *                             El técnico siempre lo recibe.
     */
    public static function enviar(string $clave, array $vars, ?int $tecnicoUserId = null, ?string $url = null, ?string $dedupeRoles = null, ?string $urlRoles = null): void
    {
        try {
            $p = PlantillaNotificacion::where('clave', $clave)->where('activa', true)->first();
            if (! $p) {
                return;
            }

            if ($p->avisar_tecnico && $tecnicoUserId && ($u = User::find($tecnicoUserId))) {
                self::notificar($p, $u, $vars, $url);
            }

            $roles = array_values(array_filter((array) $p->roles_extra));
            $roles = \Spatie\Permission\Models\Role::whereIn('name', $roles)->pluck('name')->all(); // ignora roles que ya no existan
            if ($roles && ($dedupeRoles === null || Cache::add($dedupeRoles, 1, now()->addMinutes(5)))) {
                foreach (User::role($roles)->get() as $u) {
                    if ($u->id !== $tecnicoUserId || ! $p->avisar_tecnico) {
                        self::notificar($p, $u, $vars, $urlRoles);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Notificador {$clave} falló: " . $e->getMessage());
        }
    }

    /** Envía la plantilla con datos de ejemplo al usuario indicado, sin importar destinatarios. */
    public static function prueba(PlantillaNotificacion $p, User $u): void
    {
        self::notificar($p, $u, PlantillaNotificacion::EJEMPLO, url('/tecnico'));
    }

    private static function notificar(PlantillaNotificacion $p, User $u, array $vars, ?string $url): void
    {
        $map = [];
        foreach ($vars as $k => $v) {
            $map['{' . $k . '}'] = (string) $v;
        }
        $r = fn (string $s) => strtr($s, $map);

        $u->notify(new PlantillaAviso(
            titulo: $r($p->titulo),
            cuerpo: $r($p->cuerpo),
            pushTitulo: $r($p->push_titulo),
            pushCuerpo: $r($p->push_cuerpo),
            url: $url,
            correo: $p->correo_activo && filled($u->email),
            push: $p->push_activo,
        ));
    }
}
