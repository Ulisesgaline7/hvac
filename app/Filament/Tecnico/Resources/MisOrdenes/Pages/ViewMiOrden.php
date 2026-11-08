<?php

namespace App\Filament\Tecnico\Resources\MisOrdenes\Pages;

use App\Enums\EstadoOt;
use App\Filament\Tecnico\Resources\MisOrdenes\MisOrdenesResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewMiOrden extends ViewRecord
{
    protected static string $resource = MisOrdenesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->transicion('iniciar', 'Iniciar', EstadoOt::Asignada, EstadoOt::EnProceso, 'heroicon-o-play'),
            $this->transicion('reanudar', 'Reanudar', EstadoOt::EnEspera, EstadoOt::EnProceso, 'heroicon-o-arrow-path'),
            $this->transicion('completar', 'Completar', EstadoOt::EnProceso, EstadoOt::CompletadaTecnico, 'heroicon-o-check'),
            ...\App\Support\AccionesEntregables::hacer(fn () => $this->record),
            $this->acuerdoAction(),
            $this->pasoSupervisor('revisar', 'Iniciar revisión', EstadoOt::CompletadaTecnico, EstadoOt::EnRevision, 'heroicon-o-eye', false),
            $this->pasoSupervisor('cerrar', 'Cerrar OT', EstadoOt::EnRevision, EstadoOt::Cerrada, 'heroicon-o-lock-closed', false),
            $this->pasoSupervisor('devolver', 'Devolver al técnico', EstadoOt::EnRevision, EstadoOt::EnProceso, 'heroicon-o-arrow-uturn-left', true),
        ];
    }

    private function tecnicoActual(): ?\App\Models\Tecnico
    {
        return \App\Models\Tecnico::where('user_id', auth()->id())->first();
    }

    private function acuerdoAction(): Action
    {
        $svc = app(\App\Services\AcuerdoTecnico::class);

        return Action::make('aceptarAcuerdo')
            ->label('Aceptar acuerdo de técnico')
            ->icon('heroicon-o-document-check')
            ->color('warning')
            ->visible(fn () => ($t = $this->tecnicoActual()) && $svc->pendiente($t))
            ->modalHeading(fn () => $svc->documento()?->nombre ?? 'Acuerdo')
            ->modalDescription(fn () => new \Illuminate\Support\HtmlString(
                '<div style="max-height:45vh;overflow:auto;text-align:left">'
                . ($svc->documento() ? $svc->textoSeguro($svc->documento()) : '') . '</div>'
            ))
            ->modalSubmitActionLabel('Firmar y aceptar')
            ->schema([
                \Filament\Forms\Components\TextInput::make('nombre')->label('Nombre completo')->required()->maxLength(150),
                \App\Forms\Components\FirmaPad::make('firma')->label('Firma')->required(),
                \Filament\Forms\Components\Checkbox::make('acepto')->label('He leído y acepto este acuerdo.')->accepted(),
            ])
            ->action(function (array $data) use ($svc) {
                $t = $this->tecnicoActual();
                if (! $t) {
                    return;
                }
                try {
                    $svc->aceptar($t, $data['nombre'], (string) $data['firma'], request()->ip(), substr((string) request()->userAgent(), 0, 500));
                } catch (\DomainException $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();

                    return;
                }
                Notification::make()->success()->title('Acuerdo firmado')->send();
            });
    }

    /** Con RLS solo se ven las filas propias de la cuadrilla: si aparece una fila «lider», es la mía. */
    private function esLider(): bool
    {
        return \Illuminate\Support\Facades\DB::table('orden_trabajo_tecnico')
            ->where('orden_trabajo_id', $this->record->id)->where('rol', 'lider')->exists();
    }

    /** Pasos del supervisor asignado. Visible solo para él; la autorización real la valida OrdenTrabajo::cambiarEstado. */
    private function pasoSupervisor(string $nombre, string $etiqueta, EstadoOt $desde, EstadoOt $hacia, string $icono, bool $pideMotivo): Action
    {
        return Action::make($nombre)
            ->label($etiqueta)
            ->icon($icono)
            ->color($hacia === EstadoOt::EnProceso ? 'warning' : 'success')
            ->visible(fn () => $this->record->estado === $desde && $this->record->esSupervisorDe(auth()->user()))
            ->schema([
                \Filament\Forms\Components\Textarea::make('motivo')
                    ->label($pideMotivo ? 'Motivo de la devolución' : 'Observaciones (obligatorio si hay valores fuera de rango)')
                    ->required($pideMotivo),
            ])
            ->action(function (array $data) use ($hacia) {
                try {
                    $this->record->cambiarEstado($hacia, $data['motivo'] ?? null);
                } catch (\DomainException $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();

                    return;
                }

                $this->record->refresh();
                Notification::make()->success()->title('Estado actualizado')->send();
            });
    }

    private function transicion(string $nombre, string $etiqueta, EstadoOt $desde, EstadoOt $hacia, string $icono): Action
    {
        return Action::make($nombre)
            ->label($etiqueta)
            ->icon($icono)
            ->requiresConfirmation()
            ->visible(fn () => $this->record->estado === $desde && $this->esLider())
            ->action(function () use ($hacia) {
                try {
                    $this->record->cambiarEstado($hacia);
                } catch (\DomainException $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();

                    return;
                }

                $this->record->refresh();
                Notification::make()->success()->title('Estado actualizado')->send();
            });
    }
}
