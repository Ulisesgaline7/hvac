<?php

namespace App\Filament\Resources\BitacoraCobros;

use App\Filament\Concerns\EsBitacora;
use App\Filament\Resources\BitacoraCobros\Pages\ListBitacoraCobros;
use Filament\Resources\Resource;
use Spatie\Activitylog\Models\Activity;

class BitacoraCobrosResource extends Resource
{
    use EsBitacora;

    protected static ?string $model = Activity::class;

    protected static ?string $slug = 'bitacora-cobros';

    protected static string|\UnitEnum|null $navigationGroup = 'Bitácoras';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Contratos y cobros';

    protected static ?string $modelLabel = 'registro';

    protected static ?string $pluralModelLabel = 'Bitácora · Contratos y cobros';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static function clave(): string
    {
        return 'cobros';
    }

    public static function getPages(): array
    {
        return ['index' => ListBitacoraCobros::route('/')];
    }
}
