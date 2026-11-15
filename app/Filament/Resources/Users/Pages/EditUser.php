<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $rolNuevo = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->hidden(fn () => $this->record->id === auth()->id()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->rolNuevo = $data['rol'] ?? null;
        unset($data['rol']);

        if (! in_array($this->rolNuevo, ['cliente_admin', 'cliente_consulta'])) {
            $data['cliente_id'] = null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->rolNuevo) {
            $this->record->syncRoles([$this->rolNuevo]);
        }
    }
}
