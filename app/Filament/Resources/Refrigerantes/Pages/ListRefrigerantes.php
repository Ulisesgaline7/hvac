<?php

namespace App\Filament\Resources\Refrigerantes\Pages;

use App\Filament\Resources\Refrigerantes\RefrigeranteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRefrigerantes extends ListRecords
{
    protected static string $resource = RefrigeranteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
