<?php

namespace App\Filament\Resources\Oportunidades\Pages;

use App\Filament\Resources\Oportunidades\OportunidadResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOportunidad extends EditRecord
{
    protected static string $resource = OportunidadResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
