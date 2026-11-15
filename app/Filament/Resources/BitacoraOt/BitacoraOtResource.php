<?php

namespace App\Filament\Resources\BitacoraOt;

use App\Filament\Concerns\EsBitacora;
use App\Filament\Resources\BitacoraOt\Pages\ListBitacoraOt;
use Filament\Resources\Resource;
use Spatie\Activitylog\Models\Activity;

class BitacoraOtResource extends Resource
{
    use EsBitacora;

    protected static ?string $model = Activity::class;

    protected static ?string $slug = 'bitacora-ot';

    protected static string|\UnitEnum|null $navigationGroup = 'Bitácoras';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Órdenes de trabajo';

    protected static ?string $modelLabel = 'registro';

    protected static ?string $pluralModelLabel = 'Bitácora · Órdenes de trabajo';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static function clave(): string
    {
        return 'ot';
    }

    public static function getPages(): array
    {
        return ['index' => ListBitacoraOt::route('/')];
    }
}
