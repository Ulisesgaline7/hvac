<?php

namespace App\Filament\Resources\ServiciosCatalogo\Pages;

use App\Filament\Resources\ServiciosCatalogo\ServicioCatalogoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageServiciosCatalogo extends ManageRecords
{
    protected static string $resource = ServicioCatalogoResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
