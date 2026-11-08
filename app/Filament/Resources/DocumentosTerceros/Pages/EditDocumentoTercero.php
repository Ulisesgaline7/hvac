<?php

namespace App\Filament\Resources\DocumentosTerceros\Pages;

use App\Filament\Resources\DocumentosTerceros\DocumentoTerceroResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDocumentoTercero extends EditRecord
{
    protected static string $resource = DocumentoTerceroResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
