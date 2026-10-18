<?php

namespace App\Filament\Resources\PlantillaFormularios\Pages;

use App\Filament\Resources\PlantillaFormularios\PlantillaFormularioResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPlantillaFormulario extends EditRecord
{
    protected static string $resource = PlantillaFormularioResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['campos'] = $this->record->ultimaVersion?->definicion ?? [];

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $campos = array_values($data['campos'] ?? []);
        unset($data['campos']);

        $record->update($data);

        if ($campos !== ($record->ultimaVersion?->definicion ?? [])) {
            $record->publicar($campos);
        }

        return $record;
    }
}
