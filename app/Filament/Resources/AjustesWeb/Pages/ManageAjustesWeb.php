<?php

namespace App\Filament\Resources\AjustesWeb\Pages;

use App\Filament\Resources\AjustesWeb\AjusteWebResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAjustesWeb extends ManageRecords
{
    protected static string $resource = AjusteWebResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
