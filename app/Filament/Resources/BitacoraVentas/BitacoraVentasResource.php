<?php

namespace App\Filament\Resources\BitacoraVentas;

use App\Filament\Concerns\EsBitacora;
use App\Filament\Resources\BitacoraVentas\Pages\ListBitacoraVentas;
use Filament\Resources\Resource;
use Spatie\Activitylog\Models\Activity;

class BitacoraVentasResource extends Resource
{
    use EsBitacora;

    protected static ?string $model = Activity::class;

    protected static ?string $slug = 'bitacora-ventas';

    protected static string|\UnitEnum|null $navigationGroup = 'Bitácoras';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Cotizaciones y ventas';

    protected static ?string $modelLabel = 'registro';

    protected static ?string $pluralModelLabel = 'Bitácora · Cotizaciones y ventas';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static function clave(): string
    {
        return 'ventas';
    }

    public static function getPages(): array
    {
        return ['index' => ListBitacoraVentas::route('/')];
    }
}
