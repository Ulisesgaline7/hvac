<?php

namespace App\Filament\Resources\Insumos\Pages;

use App\Filament\Resources\Insumos\InsumoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInsumo extends CreateRecord
{
    protected static string $resource = InsumoResource::class;

    protected float $stockInicial = 0;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->stockInicial = (float) ($data['stock_inicial'] ?? 0);
        unset($data['stock_inicial']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->stockInicial > 0) {
            $this->record->registrarMovimiento('entrada', $this->stockInicial, null, (float) $this->record->costo, 'Stock inicial');
        }
    }
}
