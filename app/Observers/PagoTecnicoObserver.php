<?php

namespace App\Observers;

use App\Models\PagoTecnico;
use App\Services\NotificadorTecnico;
use Illuminate\Support\Facades\DB;

class PagoTecnicoObserver
{
    /** Solo avisa si la transacción se confirma. */
    public bool $afterCommit = true;

    public function created(PagoTecnico $p): void
    {
        if ($p->estado === 'sin_tarifa') {
            $this->avisar($p, 'pago_sin_tarifa', "notif:sin_tarifa:{$p->orden_trabajo_id}");
        } elseif ($p->estado === 'retorno') {
            $this->avisar($p, 'retorno_registrado', "notif:retorno:{$p->orden_trabajo_id}");
        }
    }

    public function updated(PagoTecnico $p): void
    {
        if ($p->wasChanged('estado') && $p->estado === 'aprobado') {
            $this->avisar($p, 'pago_aprobado');
        }
    }

    private function avisar(PagoTecnico $p, string $clave, ?string $dedupe = null): void
    {
        $t = DB::table('tecnicos')->join('users', 'users.id', '=', 'tecnicos.user_id')
            ->where('tecnicos.id', $p->tecnico_id)->first(['users.id as uid', 'users.name']);

        NotificadorTecnico::enviar($clave, [
            'tecnico' => $t->name ?? '',
            'ot' => (string) DB::table('ordenes_trabajo')->where('id', $p->orden_trabajo_id)->value('numero'),
            'concepto' => $p->concepto,
            'monto' => 'L ' . number_format((float) $p->monto, 2),
            'rol' => $p->rol,
        ], $t->uid ?? null, url('/tecnico/pagos-ot/pago-ots'), $dedupe);
    }
}
