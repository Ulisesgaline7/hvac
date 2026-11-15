<?php

namespace App\Filament\Portal\Resources\Garantias;

use App\Filament\Portal\Resources\Garantias\Pages\ListGarantiasPortal;
use App\Filament\Portal\Resources\Solicitudes\SolicitudPortalResource;
use App\Models\Garantia;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GarantiaPortalResource extends Resource
{
    protected static ?string $model = Garantia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $slug = 'garantias';

    protected static ?string $modelLabel = 'Garantía';

    protected static ?string $pluralModelLabel = 'Mis garantías';

    public static function getEloquentQuery(): Builder
    {
        $clienteId = auth()->user()?->cliente_id;

        return parent::getEloquentQuery()
            ->when(
                $clienteId,
                fn (Builder $q) => $q->whereHas('ordenTrabajo', fn (Builder $o) => $o->where('cliente_id', $clienteId)),
                fn (Builder $q) => $q->whereRaw('1 = 0')
            );
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('hasta', 'desc')
            ->columns([
                TextColumn::make('numero')->label('Certificado')->searchable(),
                TextColumn::make('ordenTrabajo.numero')->label('Orden de trabajo'),
                TextColumn::make('categoria'),
                TextColumn::make('dias')->label('Días'),
                TextColumn::make('desde')->date('d/m/Y'),
                TextColumn::make('hasta')->label('Vence')->date('d/m/Y'),
                TextColumn::make('estado')->badge(),
            ])
            ->recordActions([
                Action::make('solicitar')
                    ->label('Solicitar garantía')
                    ->icon(Heroicon::OutlinedWrenchScrewdriver)
                    ->url(fn (Garantia $record) => SolicitudPortalResource::getUrl('create', ['garantia' => $record->id])),
            ]);
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Servicios';
    }

    public static function getPages(): array
    {
        return ['index' => ListGarantiasPortal::route('/')];
    }
}
