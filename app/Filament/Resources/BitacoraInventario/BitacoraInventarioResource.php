<?php

namespace App\Filament\Resources\BitacoraInventario;

use App\Filament\Concerns\EsBitacora;
use App\Filament\Resources\BitacoraInventario\Pages\ListBitacoraInventario;
use Filament\Resources\Resource;
use Spatie\Activitylog\Models\Activity;

class BitacoraInventarioResource extends Resource
{
    use EsBitacora;

    protected static ?string $model = Activity::class;

    protected static ?string $slug = 'bitacora-inventario';

    protected static string|\UnitEnum|null $navigationGroup = 'Bitácoras';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Inventario y catálogos';

    protected static ?string $modelLabel = 'registro';

    protected static ?string $pluralModelLabel = 'Bitácora · Inventario y catálogos';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static function clave(): string
    {
        return 'inventario';
    }

    public static function getPages(): array
    {
        return ['index' => ListBitacoraInventario::route('/')];
    }
}
