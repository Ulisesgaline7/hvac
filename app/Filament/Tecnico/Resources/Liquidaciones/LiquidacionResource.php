<?php

namespace App\Filament\Tecnico\Resources\Liquidaciones;

use App\Filament\Tecnico\Resources\Liquidaciones\Pages\ListLiquidaciones;
use App\Models\LiquidacionTecnico;
use App\Support\TecnicoActual;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LiquidacionResource extends Resource
{
    protected static ?string $model = LiquidacionTecnico::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Mis liquidaciones';
    protected static ?string $modelLabel = 'liquidación';
    protected static ?string $pluralModelLabel = 'liquidaciones';
    protected static ?int $navigationSort = 20;
    protected static ?string $slug = 'liquidaciones';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tecnico_id', TecnicoActual::id() ?? 0)
            ->where('estado', '!=', 'borrador');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('semana_inicio', 'desc')
            ->recordActions([
                \Filament\Actions\Action::make('comprobante')
                    ->label('Comprobante')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn ($record) => route('liquidaciones.comprobante', $record->id))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->estado === 'pagada' && filled($record->numero)),
            ])
            ->columns([
                TextColumn::make('semana_inicio')->label('Semana del')->date('d/m/Y')->sortable(),
                TextColumn::make('semana_fin')->label('al')->date('d/m/Y'),
                TextColumn::make('bruto')->label('Bruto')->money('HNL'),
                TextColumn::make('retencion')->label('Retención')->money('HNL'),
                TextColumn::make('neto')->label('Neto')->money('HNL')->weight('bold'),
                TextColumn::make('estado')->label('Estado')->badge()
                    ->color(fn ($state) => $state === 'pagada' ? 'success' : 'warning'),
                TextColumn::make('pagada_en')->label('Pagada')->date('d/m/Y'),
                TextColumn::make('referencia_pago')->label('Referencia'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListLiquidaciones::route('/')];
    }
}
