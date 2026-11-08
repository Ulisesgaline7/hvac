<?php

namespace App\Filament\Resources\Equipos\Pages;

use App\Filament\Resources\Equipos\EquipoResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEquipo extends ViewRecord
{
    protected static string $resource = EquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('etiqueta')
                ->label('Etiqueta QR')
                ->icon('heroicon-o-qr-code')
                ->color('gray')
                ->url(fn () => route('etiquetas.equipo', $this->record), shouldOpenInNewTab: true),
            EditAction::make(),
        ];
    }
}
