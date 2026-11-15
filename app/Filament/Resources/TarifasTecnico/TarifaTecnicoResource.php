<?php

namespace App\Filament\Resources\TarifasTecnico;

use App\Filament\Resources\TarifasTecnico\Pages\ManageTarifasTecnico;
use App\Models\TarifaTecnico;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TarifaTecnicoResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Finanzas';

    protected static ?int $navigationSort = 5;

    protected static ?string $model = TarifaTecnico::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $slug = 'tarifas-tecnico';
    protected static ?string $modelLabel = 'tarifa de técnico';
    protected static ?string $pluralModelLabel = 'Tarifas de técnicos';

    public static function getNavigationGroup(): ?string
    {
        return 'Operación';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['administrador', 'coordinador']) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('codigo')->label('Código del servicio (T-xx)')->required()->maxLength(20)->unique(ignoreRecord: true),
            TextInput::make('monto')->label('Pago al técnico (L)')->numeric()->prefix('L')->minValue(0)->required(),
            TextInput::make('nota')->label('Nota')->maxLength(255)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('codigo')
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable()->sortable(),
                TextColumn::make('monto')->label('Pago')->money('HNL')->sortable(),
                TextColumn::make('nota')->label('Nota')->wrap(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageTarifasTecnico::route('/')];
    }
}
