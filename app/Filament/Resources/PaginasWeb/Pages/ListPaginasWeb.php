<?php

namespace App\Filament\Resources\PaginasWeb\Pages;

use App\Filament\Resources\PaginasWeb\PaginaWebResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaginasWeb extends ListRecords
{
    protected static string $resource = PaginaWebResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
