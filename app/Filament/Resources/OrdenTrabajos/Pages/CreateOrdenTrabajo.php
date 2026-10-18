<?php

namespace App\Filament\Resources\OrdenTrabajos\Pages;

use App\Enums\EstadoOt;
use App\Filament\Resources\OrdenTrabajos\OrdenTrabajoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrdenTrabajo extends CreateRecord
{
    protected static string $resource = OrdenTrabajoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['estado'] = empty($data['tecnico_id']) ? EstadoOt::Pendiente->value : EstadoOt::Asignada->value;
        $data['fecha_original'] = $data['fecha_programada'] ?? null;

        return $data;
    }
}
