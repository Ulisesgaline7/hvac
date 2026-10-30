<?php

namespace App\Filament\Resources\PaginasWeb\Pages;

use App\Filament\Resources\PaginasWeb\PaginaWebResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaginaWeb extends EditRecord
{
    protected static string $resource = PaginaWebResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
