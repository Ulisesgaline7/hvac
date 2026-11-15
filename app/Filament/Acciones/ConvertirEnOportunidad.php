<?php

namespace App\Filament\Acciones;

use App\Models\Contacto;
use App\Services\Crm;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ConvertirEnOportunidad
{
    public static function make(): Action
    {
        return Action::make('aOportunidad')
            ->label('Convertir en oportunidad')
            ->icon('heroicon-o-arrow-trending-up')
            ->visible(fn (Contacto $record): bool => empty($record->oportunidad_id))
            ->requiresConfirmation()
            ->action(function (Contacto $record) {
                Crm::desdeContacto($record);
                Notification::make()->success()->title('Oportunidad creada')->send();
            });
    }
}
