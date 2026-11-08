<?php

namespace App\Filament\Resources\DocumentosTerceros\Pages;

use App\Filament\Resources\DocumentosTerceros\DocumentoTerceroResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocumentosTerceros extends ListRecords
{
    protected static string $resource = DocumentoTerceroResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
