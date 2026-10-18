<?php

namespace App\Filament\Resources\Contratos\Pages;

use App\Enums\EstadoContrato;
use App\Filament\Resources\Contratos\ContratoResource;
use App\Services\GeneradorCalendario;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditContrato extends EditRecord
{
    protected static string $resource = ContratoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('activar')
                ->label('Activar y generar calendario')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn () => $this->record->estado === EstadoContrato::Borrador)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['estado' => EstadoContrato::Activo]);
                    $creadas = app(GeneradorCalendario::class)->generar($this->record->fresh());
                    $this->refreshFormData(['estado']);
                    Notification::make()->success()->title("Contrato activado. OT creadas: $creadas")->send();
                }),
            Action::make('cancelar')
                ->label('Cancelar contrato')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->estado === EstadoContrato::Activo)
                ->requiresConfirmation()
                ->modalDescription('Se cancelan las OT preventivas pendientes futuras. Las iniciadas y cerradas no se tocan.')
                ->action(function () {
                    $this->record->update(['estado' => EstadoContrato::Cancelado]);
                    $r = app(GeneradorCalendario::class)->recalcular($this->record->fresh());
                    $this->refreshFormData(['estado']);
                    Notification::make()->success()->title("Contrato cancelado. OT canceladas: {$r['canceladas']}")->send();
                }),
            Action::make('recalcular')
                ->label('Recalcular calendario')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn () => $this->record->estado === EstadoContrato::Activo)
                ->action(function () {
                    $r = app(GeneradorCalendario::class)->recalcular($this->record->fresh());
                    Notification::make()->success()->title("Canceladas: {$r['canceladas']} · Creadas: {$r['creadas']}")->send();
                }),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->estado === EstadoContrato::Activo) {
            app(GeneradorCalendario::class)->recalcular($this->record->fresh());
        }
    }
}
