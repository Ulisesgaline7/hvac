<?php

namespace App\Filament\Resources\BitacoraRrhh;

use App\Filament\Concerns\EsBitacora;
use App\Filament\Resources\BitacoraRrhh\Pages\ListBitacoraRrhh;
use Filament\Resources\Resource;
use Spatie\Activitylog\Models\Activity;

class BitacoraRrhhResource extends Resource
{
    use EsBitacora;

    protected static ?string $model = Activity::class;

    protected static ?string $slug = 'bitacora-rrhh';

    protected static string|\UnitEnum|null $navigationGroup = 'Bitácoras';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Recursos humanos';

    protected static ?string $modelLabel = 'registro';

    protected static ?string $pluralModelLabel = 'Bitácora · Recursos humanos';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static function clave(): string
    {
        return 'rrhh';
    }

    public static function getPages(): array
    {
        return ['index' => ListBitacoraRrhh::route('/')];
    }
}
