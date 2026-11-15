<?php

namespace App\Filament\Portal\Resources\Solicitudes;

use App\Filament\Portal\Resources\Solicitudes\Pages\CreateSolicitudPortal;
use App\Filament\Portal\Resources\Solicitudes\Pages\ListSolicitudesPortal;
use App\Models\Garantia;
use App\Models\OrdenTrabajo;
use App\Models\SolicitudCliente;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SolicitudPortalResource extends Resource
{
    protected static ?string $model = SolicitudCliente::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $slug = 'solicitudes';

    protected static ?string $modelLabel = 'Solicitud';

    protected static ?string $pluralModelLabel = 'Solicitudes y reclamos';

    public static function getEloquentQuery(): Builder
    {
        $clienteId = auth()->user()?->cliente_id;

        return parent::getEloquentQuery()
            ->when($clienteId, fn (Builder $q) => $q->where('cliente_id', $clienteId), fn (Builder $q) => $q->whereRaw('1 = 0'));
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->cliente_id;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        $clienteId = auth()->user()?->cliente_id;

        return $schema->components([
            Select::make('tipo')->options([
                'servicio' => 'Pedir un servicio',
                'reclamo' => 'Reclamo',
                'garantia' => 'Solicitar garantía',
                'otro' => 'Otro',
            ])->default(fn () => request()->integer('garantia') ? 'garantia' : null)->required(),
            Select::make('orden_trabajo_id')->label('Orden de trabajo (opcional)')
                ->options(fn () => OrdenTrabajo::where('cliente_id', $clienteId)->orderByDesc('id')->pluck('numero', 'id')->all()),
            Select::make('garantia_id')->label('Garantía (opcional)')
                ->default(fn () => request()->integer('garantia') ?: null)
                ->options(fn () => Garantia::whereHas('ordenTrabajo', fn (Builder $o) => $o->where('cliente_id', $clienteId))->pluck('numero', 'id')->all()),
            Textarea::make('descripcion')->label('Descripción')->required()->rows(5)->maxLength(2000),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
                TextColumn::make('tipo')->badge(),
                TextColumn::make('descripcion')->limit(60),
                TextColumn::make('estado')->badge(),
                TextColumn::make('respuesta')->limit(60),
            ]);
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Soporte';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSolicitudesPortal::route('/'),
            'create' => CreateSolicitudPortal::route('/create'),
        ];
    }
}
