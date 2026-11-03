<?php

namespace App\Filament\Resources\BibliotecaDocumentos\Pages;

use App\Filament\Resources\BibliotecaDocumentos\BibliotecaDocumentoResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDocumento extends EditRecord
{
    protected static string $resource = BibliotecaDocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')->label('Ver PDF')->icon('heroicon-o-document-arrow-down')
                ->url(fn () => route('documentos.pdf', $this->getRecord()))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['actualizado_por'] = auth()->id();

        return $data;
    }
}
