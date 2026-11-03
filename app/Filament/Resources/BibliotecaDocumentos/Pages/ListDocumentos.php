<?php

namespace App\Filament\Resources\BibliotecaDocumentos\Pages;

use App\Filament\Resources\BibliotecaDocumentos\BibliotecaDocumentoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocumentos extends ListRecords
{
    protected static string $resource = BibliotecaDocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
