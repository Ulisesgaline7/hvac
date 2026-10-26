<?php

namespace App\Filament\Resources\ServiciosWeb\Pages;

use App\Filament\Resources\ServiciosWeb\ServicioWebResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiciosWeb extends ListRecords
{
    protected static string $resource = ServicioWebResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Agregar servicio')];
    }
}
