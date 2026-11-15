<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Oportunidad;
use App\Providers\RlsServiceProvider;

class CrmCotizacion
{
    public static function crearDesde(Oportunidad $o): Cotizacion
    {
        return RlsServiceProvider::comoInternoConUsuario(function () use ($o) {
            if (! $o->cliente_id) {
                $cliente = Cliente::create([
                    'razon_social' => $o->prospecto_nombre ?: $o->titulo,
                    'contactos' => [[
                        'nombre' => $o->prospecto_nombre,
                        'telefono' => $o->prospecto_telefono,
                        'email' => $o->prospecto_email,
                    ]],
                ]);
                $o->update(['cliente_id' => $cliente->id]);
            }

            $c = Cotizacion::create([
                'cliente_id' => $o->cliente_id,
                'oportunidad_id' => $o->id,
                'vendedor_id' => $o->responsable_id,
            ]);

            if (in_array($o->etapa, ['nuevo', 'contactado', 'levantamiento'], true)) {
                $o->update(['etapa' => 'cotizado']);
            }

            return $c;
        });
    }

    public static function marcarGanada(Cotizacion $c): void
    {
        if (! $c->oportunidad_id) {
            return;
        }
        RlsServiceProvider::comoInterno(function () use ($c) {
            $o = Oportunidad::find($c->oportunidad_id);
            if ($o && $o->etapa !== 'ganada') {
                $o->update(['etapa' => 'ganada', 'monto_estimado' => $c->total]);
            }
        });
    }
}
