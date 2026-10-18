<?php

namespace App\Filament\Resources\Refrigerantes\Pages;

use App\Filament\Resources\Refrigerantes\RefrigeranteResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditRefrigerante extends EditRecord
{
    protected static string $resource = RefrigeranteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
