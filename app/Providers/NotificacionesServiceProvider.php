<?php

namespace App\Providers;

use App\Enums\EstadoOt;
use App\Models\Cotizacion;
use App\Models\OrdenTrabajo;
use App\Models\Pago;
use App\Models\User;
use App\Notifications\AvisoPanel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class NotificacionesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        OrdenTrabajo::saved(function (OrdenTrabajo $ot) {
            $this->seguro(function () use ($ot) {
                $num = $ot->numero ?? $ot->id;
                $urlTec = url("/tecnico/mis-ordenes/{$ot->id}");
                $urlAdm = url("/admin/ordenes-trabajo/{$ot->id}/edit");
                $usuarioTec = $ot->tecnico?->user;

                if ($ot->tecnico_id && ($ot->wasRecentlyCreated || $ot->wasChanged('tecnico_id'))) {
                    $this->avisar($usuarioTec, "Nueva orden asignada", "Se te asignó la OT #{$num}.", $urlTec, 'heroicon-o-wrench-screwdriver');
                }

                if (! $ot->wasChanged('estado')) {
                    return;
                }

                $nuevo = $this->valor($ot->estado);
                $anterior = $this->valor($ot->getOriginal('estado'));

                if (in_array($nuevo, [EstadoOt::CompletadaTecnico->value, EstadoOt::EnRevision->value], true)) {
                    $this->avisar($this->admins(), "OT lista para revisión", "La OT #{$num} está lista para revisar.", $urlAdm, 'heroicon-o-clipboard-document-check');
                } elseif ($nuevo === EstadoOt::EnProceso->value && $anterior === EstadoOt::EnRevision->value) {
                    $this->avisar($usuarioTec, "OT devuelta para corrección", "La OT #{$num} fue devuelta a En proceso.", $urlTec, 'heroicon-o-arrow-uturn-left');
                } elseif ($nuevo === EstadoOt::Cancelada->value) {
                    $this->avisar($usuarioTec, "OT cancelada", "La OT #{$num} fue cancelada.", $urlTec, 'heroicon-o-x-circle');
                }
            });
        });

        Cotizacion::updated(function (Cotizacion $c) {
            $this->seguro(function () use ($c) {
                if (! $c->wasChanged('estado')) {
                    return;
                }
                $estado = $this->valor($c->estado);
                if (! in_array($estado, ['aprobada', 'rechazada'], true)) {
                    return;
                }
                $num = $c->numero ?? $c->id;
                $this->avisar(
                    $this->admins(),
                    "Cotización {$estado}",
                    "La cotización #{$num} fue {$estado} por el cliente.",
                    url("/admin/cotizaciones/{$c->id}/edit"),
                    $estado === 'aprobada' ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'
                );
            });
        });

        Pago::created(function (Pago $p) {
            $this->seguro(function () use ($p) {
                $monto = $p->monto ?? null;
                $this->avisar(
                    $this->admins(),
                    "Pago registrado",
                    'Se registró un pago' . ($monto !== null ? " por {$monto}" : '') . ' (factura #' . ($p->factura_id ?? '?') . ').',
                    $p->factura_id ? url("/admin/facturas/{$p->factura_id}") : null,
                    'heroicon-o-banknotes'
                );
            });
        });
    }

    private function admins()
    {
        return User::role(['administrador', 'coordinador'])->get();
    }

    private function valor(mixed $v): ?string
    {
        return $v instanceof \BackedEnum ? (string) $v->value : ($v === null ? null : (string) $v);
    }

    private function avisar(mixed $destino, string $titulo, string $cuerpo, ?string $url, string $icono): void
    {
        if (! $destino) {
            return;
        }
        $correo = config('mail.default') === 'smtp';
        $destinatarios = $destino instanceof \Illuminate\Support\Collection ? $destino : collect([$destino]);
        foreach ($destinatarios as $u) {
            $u->notify(new AvisoPanel($titulo, $cuerpo, $url, $correo, $icono));
        }
    }

    private function seguro(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            Log::warning('Notificación no enviada: ' . $e->getMessage());
        }
    }
}
