<?php

namespace App\Filament\Portal\Resources\Solicitudes\Pages;

use App\Filament\Portal\Resources\Solicitudes\SolicitudPortalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSolicitudPortal extends CreateRecord
{
    protected static string $resource = SolicitudPortalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['cliente_id'] = auth()->user()->cliente_id;
        $data['estado'] = 'nueva';
        $data['creado_por'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
