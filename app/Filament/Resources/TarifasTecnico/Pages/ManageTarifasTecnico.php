<?php

namespace App\Filament\Resources\TarifasTecnico\Pages;

use App\Filament\Resources\TarifasTecnico\TarifaTecnicoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTarifasTecnico extends ManageRecords
{
    protected static string $resource = TarifaTecnicoResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
