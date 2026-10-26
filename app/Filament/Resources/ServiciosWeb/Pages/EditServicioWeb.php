<?php

namespace App\Filament\Resources\ServiciosWeb\Pages;

use App\Filament\Resources\ServiciosWeb\ServicioWebResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServicioWeb extends EditRecord
{
    protected static string $resource = ServicioWebResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
