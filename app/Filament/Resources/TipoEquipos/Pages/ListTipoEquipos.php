<?php

namespace App\Filament\Resources\TipoEquipos\Pages;

use App\Filament\Resources\TipoEquipos\TipoEquipoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTipoEquipos extends ListRecords
{
    protected static string $resource = TipoEquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
