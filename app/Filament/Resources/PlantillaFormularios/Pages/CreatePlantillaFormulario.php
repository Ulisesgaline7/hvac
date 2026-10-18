<?php

namespace App\Filament\Resources\PlantillaFormularios\Pages;

use App\Filament\Resources\PlantillaFormularios\PlantillaFormularioResource;
use App\Models\PlantillaFormulario;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePlantillaFormulario extends CreateRecord
{
    protected static string $resource = PlantillaFormularioResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $campos = $data['campos'] ?? [];
        unset($data['campos']);

        $plantilla = PlantillaFormulario::create($data);
        $plantilla->publicar($campos);

        return $plantilla;
    }
}
