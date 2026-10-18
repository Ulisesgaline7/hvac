<?php

namespace App\Filament\Resources\TipoEquipos\Pages;

use App\Filament\Resources\TipoEquipos\TipoEquipoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditTipoEquipo extends EditRecord
{
    protected static string $resource = TipoEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
