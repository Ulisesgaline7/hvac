<?php

namespace App\Services;

use App\Models\Contacto;
use App\Models\Oportunidad;
use App\Models\User;
use App\Providers\RlsServiceProvider;

class Crm
{
    public static function siguienteComercial(): ?int
    {
        return User::role('comercial')->get()
            ->sortBy(fn (User $u) => Oportunidad::where('responsable_id', $u->id)->whereNotIn('etapa', ['ganada', 'perdida'])->count())
            ->first()?->id;
    }

    public static function desdeContacto(Contacto $c, ?int $responsableId = null): Oportunidad
    {
        return RlsServiceProvider::comoInterno(function () use ($c, $responsableId) {
            if ($c->oportunidad_id && ($o = Oportunidad::find($c->oportunidad_id))) {
                return $o;
            }

            $o = Oportunidad::create([
                'titulo' => trim(($c->servicio ?: 'Consulta web') . ' - ' . $c->nombre),
                'prospecto_nombre' => $c->nombre,
                'prospecto_telefono' => $c->telefono,
                'prospecto_email' => $c->email,
                'responsable_id' => $responsableId ?? self::siguienteComercial(),
                'origen' => 'web',
                'notas' => trim(($c->ciudad ? "Ciudad: {$c->ciudad}\n" : '') . $c->mensaje),
            ]);

            $c->forceFill(['oportunidad_id' => $o->id, 'atendido' => true])->saveQuietly();

            return $o;
        });
    }
}
