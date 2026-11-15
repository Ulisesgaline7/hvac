<?php

namespace App\Filament\Resources\Divisiones\Pages;

use App\Filament\Resources\Divisiones\DivisionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDivisiones extends ManageRecords
{
    protected static string $resource = DivisionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
