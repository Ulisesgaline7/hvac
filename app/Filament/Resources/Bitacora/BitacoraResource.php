<?php

namespace App\Filament\Resources\Bitacora;

use App\Filament\Resources\Bitacora\Pages\ListBitacora;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class BitacoraResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $slug = 'bitacora';

    protected static ?string $modelLabel = 'registro';

    protected static ?string $pluralModelLabel = 'Bitácora';

    protected static ?string $navigationLabel = 'Bitácora';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistema';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 99;

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
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['causer', 'subject']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('causer')->label('Usuario')
                    ->getStateUsing(fn (Activity $r) => $r->causer?->name ?? 'Sistema'),
                TextColumn::make('event')->label('Evento')->badge()
                    ->color(fn ($state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        'restored' => 'warning',
                        'movimiento' => 'primary',
                        default => 'gray',
                    })->placeholder('—'),
                TextColumn::make('subject_type')->label('Módulo')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '—'),
                TextColumn::make('subject_id')->label('ID')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')->label('Descripción')->searchable()->wrap(),
            ])
            ->filters([
                SelectFilter::make('event')->label('Evento')
                    ->options(fn () => Activity::query()->whereNotNull('event')->distinct()->orderBy('event')->pluck('event', 'event')->all()),
                SelectFilter::make('subject_type')->label('Módulo')
                    ->options(fn () => Activity::query()->whereNotNull('subject_type')->distinct()->pluck('subject_type')
                        ->mapWithKeys(fn ($t) => [$t => class_basename($t)])->all()),
                Filter::make('fecha')
                    ->schema([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['desde'] ?? null, fn ($query, $d) => $query->whereDate('created_at', '>=', $d))
                        ->when($data['hasta'] ?? null, fn ($query, $d) => $query->whereDate('created_at', '<=', $d))),
            ])
            ->recordActions([
                Action::make('detalle')->label('Detalle')->icon('heroicon-o-eye')
                    ->modalHeading('Detalle del registro')
                    ->modalContent(fn (Activity $record) => view('filament.bitacora.detalle', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListBitacora::route('/')];
    }
}
