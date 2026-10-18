<?php

namespace App\Filament\Resources\PlantillaFormularios\Pages;

use App\Filament\Resources\PlantillaFormularios\PlantillaFormularioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlantillaFormularios extends ListRecords
{
    protected static string $resource = PlantillaFormularioResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
