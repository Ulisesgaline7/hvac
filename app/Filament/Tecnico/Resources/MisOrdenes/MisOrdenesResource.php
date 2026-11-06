<?php

namespace App\Filament\Tecnico\Resources\MisOrdenes;

use App\Filament\Resources\OrdenTrabajos\RelationManagers\LineasRelationManager;
use App\Filament\Tecnico\Resources\MisOrdenes\Pages\ListMisOrdenes;
use App\Filament\Tecnico\Resources\MisOrdenes\Pages\ViewMiOrden;
use App\Models\OrdenTrabajo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;

class MisOrdenesResource extends Resource
{
    protected static ?string $model = OrdenTrabajo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $slug = 'mis-ordenes';

    protected static ?string $modelLabel = 'Orden de trabajo';

    protected static ?string $pluralModelLabel = 'Mis órdenes';

    protected static ?string $recordTitleAttribute = 'numero';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('fecha_programada')
            ->columns([
                TextColumn::make('numero')->label('OT')->searchable(),
                TextColumn::make('estado')->badge(),
                TextColumn::make('mi_rol')->label('Mi rol')->badge()
                    ->state(fn ($record) => [
                        'lider' => 'Líder', 'tecnico' => 'Técnico', 'ayudante' => 'Ayudante', 'supervisor' => 'Supervisor',
                    ][\Illuminate\Support\Facades\DB::table('orden_trabajo_tecnico')->where('orden_trabajo_id', $record->id)->value('rol')] ?? '—'),
                TextColumn::make('cliente.razon_social')->label('Cliente'),
                TextColumn::make('sucursal.nombre')->label('Sucursal'),
                TextColumn::make('fecha_programada')->label('Programada')->date()->sortable(),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        $e = \Filament\Infolists\Components\TextEntry::class;

        return $schema->components([
            \Filament\Schemas\Components\Section::make('Orden de trabajo')->columns(2)->columnSpanFull()->schema([
                $e::make('numero')->label('OT'),
                $e::make('estado')->badge(),
                $e::make('tipo')->label('Tipo')
                    ->state(fn ($record) => $record->tipo instanceof \BackedEnum ? $record->tipo->value : $record->tipo),
                $e::make('prioridad')->label('Prioridad')->badge(),
                $e::make('cliente.razon_social')->label('Cliente'),
                $e::make('sucursal.nombre')->label('Sucursal'),
                $e::make('fecha_programada')->label('Programada')->date()->placeholder('—'),
                $e::make('descripcion')->label('Descripción')->placeholder('—')->columnSpanFull(),
                $e::make('motivo_devolucion')->label('Motivo de devolución')->columnSpanFull()
                    ->visible(fn ($record) => filled($record->motivo_devolucion)),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [LineasRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMisOrdenes::route('/'),
            'view' => ViewMiOrden::route('/{record}'),
        ];
    }
}
