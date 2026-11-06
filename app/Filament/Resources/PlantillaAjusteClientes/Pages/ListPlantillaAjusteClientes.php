<?php

namespace App\Filament\Resources\PlantillaAjusteClientes\Pages;

use App\Filament\Resources\PlantillaAjusteClientes\PlantillaAjusteClienteResource;
use Filament\Resources\Pages\ListRecords;

class ListPlantillaAjusteClientes extends ListRecords
{
    protected static string $resource = PlantillaAjusteClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()];
    }
}
