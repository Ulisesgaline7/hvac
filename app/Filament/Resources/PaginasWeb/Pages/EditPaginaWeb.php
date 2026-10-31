<?php

namespace App\Filament\Resources\PaginasWeb\Pages;

use App\Filament\Resources\PaginasWeb\PaginaWebResource;
use App\Models\WebPagina;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaginaWeb extends EditRecord
{
    protected static string $resource = PaginaWebResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ver')->label(fn () => $this->record->publicada ? 'Ver en el sitio' : 'Vista previa')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => $this->record->es_inicio && $this->record->publicada ? url('/') : url('/' . $this->record->slug))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
