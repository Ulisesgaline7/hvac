<?php

namespace App\Filament\Concerns;

use App\Support\Bitacoras;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

trait EsBitacora
{
    abstract protected static function clave(): string;

    public static function canViewAny(): bool
    {
        return Bitacoras::puedeVer(static::clave());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return Bitacoras::tabla($table, static::clave());
    }
}
