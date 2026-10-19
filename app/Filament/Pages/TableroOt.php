<?php

namespace App\Filament\Pages;

use App\Enums\EstadoOt;
use App\Models\OrdenTrabajo;
use BackedEnum;
use DomainException;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class TableroOt extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static ?string $navigationLabel = 'Tablero de OT';

    protected static ?string $title = 'Tablero de OT';

    protected static ?string $slug = 'tablero-ot';

    protected string $view = 'filament.pages.tablero-ot';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['administrador', 'coordinador']) ?? false;
    }

    public function getColumnas(): array
    {
        $columnas = [];

        foreach (EstadoOt::cases() as $estado) {
            if ($estado === EstadoOt::Cancelada) {
                continue;
            }

            $consulta = OrdenTrabajo::with(['cliente', 'sucursal', 'tecnico.user'])->where('estado', $estado->value);

            $consulta = $estado === EstadoOt::Cerrada
                ? $consulta->orderByDesc('cerrada_en')->limit(15)
                : $consulta->orderBy('fecha_programada')->limit(50);

            $columnas[] = ['estado' => $estado, 'ordenes' => $consulta->get()];
        }

        return $columnas;
    }

    public function moverOt(int $id, string $estado): void
    {
        $ot = OrdenTrabajo::find($id);
        $nuevo = EstadoOt::tryFrom($estado);

        if (! $ot || ! $nuevo || $ot->estado === $nuevo) {
            return;
        }

        $pideMotivo = in_array($nuevo, [EstadoOt::Cancelada, EstadoOt::EnEspera], true)
            || ($ot->estado === EstadoOt::EnRevision && $nuevo === EstadoOt::EnProceso);

        if ($pideMotivo) {
            Notification::make()->warning()->title('Este cambio requiere un motivo. Usa «Cambiar estado» dentro de la OT.')->send();

            return;
        }

        if ($nuevo === EstadoOt::Asignada && ! $ot->tecnico_id) {
            Notification::make()->warning()->title('Asigna un técnico desde la OT antes de moverla a Asignada.')->send();

            return;
        }

        try {
            $ot->cambiarEstado($nuevo);
        } catch (DomainException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            return;
        }

        Notification::make()->success()->title("{$ot->numero} → {$nuevo->getLabel()}")->send();
    }
}
