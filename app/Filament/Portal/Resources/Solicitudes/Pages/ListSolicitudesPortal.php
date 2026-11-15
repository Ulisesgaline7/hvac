<?php

namespace App\Filament\Portal\Resources\Solicitudes\Pages;

use App\Filament\Portal\Resources\Solicitudes\SolicitudPortalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSolicitudesPortal extends ListRecords
{
    protected static string $resource = SolicitudPortalResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nueva solicitud')];
    }
}
