<?php

namespace App\Filament\Resources\Cilindros\Pages;

use App\Filament\Resources\Cilindros\CilindroResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCilindros extends ListRecords
{
    protected static string $resource = CilindroResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
