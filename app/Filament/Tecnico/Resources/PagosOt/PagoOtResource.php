<?php

namespace App\Filament\Tecnico\Resources\PagosOt;

use App\Filament\Tecnico\Resources\PagosOt\Pages\ListPagosOt;
use App\Models\PagoTecnico;
use App\Support\TecnicoActual;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PagoOtResource extends Resource
{
    protected static ?string $model = PagoTecnico::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationLabel = 'Detalle de pagos';
    protected static ?string $modelLabel = 'pago';
    protected static ?string $pluralModelLabel = 'detalle de pagos';
    protected static ?int $navigationSort = 21;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tecnico_id', TecnicoActual::id() ?? 0);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('ordenTrabajo.numero')->label('OT')->searchable(),
                TextColumn::make('concepto')->wrap()->searchable(),
                TextColumn::make('rol')->label('Rol')->badge(),
                TextColumn::make('porcentaje')->label('%')->suffix(' %'),
                TextColumn::make('monto')->label('Monto')->money('HNL')->weight('bold'),
                TextColumn::make('estado')->label('Estado')->badge()
                    ->color(fn ($state) => match ($state) {
                        'liquidado' => 'success',
                        'retorno' => 'danger',
                        'sin_tarifa' => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('created_at')->label('Fecha')->date('d/m/Y')->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPagosOt::route('/')];
    }
}
