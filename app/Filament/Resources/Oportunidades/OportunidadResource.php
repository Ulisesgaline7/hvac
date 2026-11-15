<?php

namespace App\Filament\Resources\Oportunidades;

use App\Filament\Resources\Oportunidades\Pages\CreateOportunidad;
use App\Filament\Resources\Oportunidades\Pages\EditOportunidad;
use App\Filament\Resources\Oportunidades\Pages\ListOportunidades;
use App\Filament\Resources\Oportunidades\RelationManagers\ActividadesRelationManager;
use App\Models\Oportunidad;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OportunidadResource extends Resource
{
    protected static ?string $model = Oportunidad::class;
    protected static ?string $modelLabel = 'oportunidad';
    protected static ?string $pluralModelLabel = 'oportunidades';
    protected static ?string $navigationLabel = 'Oportunidades (CRM)';
    protected static ?string $recordTitleAttribute = 'titulo';

    private static function esJefe(): bool
    {
        return auth()->user()?->hasAnyRole(['administrador', 'coordinador']) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['administrador', 'coordinador', 'comercial']) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();

        return self::esJefe() ? $q : $q->where('responsable_id', auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Oportunidad')->columns(2)->schema([
                TextInput::make('titulo')->label('Título')->required()->maxLength(255)->columnSpanFull(),
                Select::make('cliente_id')->label('Cliente existente')->relationship('cliente', 'razon_social')->searchable()->preload(),
                Select::make('responsable_id')->label('Responsable')->required()
                    ->options(fn () => User::role(['comercial', 'coordinador', 'administrador'])->pluck('name', 'id'))
                    ->default(fn () => auth()->id())
                    ->disabled(fn () => ! self::esJefe())->dehydrated(),
                TextInput::make('prospecto_nombre')->label('Prospecto (si no es cliente)'),
                TextInput::make('prospecto_telefono')->label('Teléfono')->tel(),
                TextInput::make('prospecto_email')->label('Correo')->email(),
                Select::make('origen')->options(['web' => 'Sitio web', 'whatsapp' => 'WhatsApp', 'referido' => 'Referido', 'llamada' => 'Llamada', 'otro' => 'Otro']),
            ]),
            Section::make('Venta')->columns(2)->schema([
                Select::make('etapa')->options(Oportunidad::ETAPAS)->default('nuevo')->required()->live(),
                TextInput::make('monto_estimado')->label('Monto estimado (L)')->numeric()->default(0),
                TextInput::make('probabilidad')->label('Probabilidad %')->numeric()->minValue(0)->maxValue(100)->default(10),
                DatePicker::make('cierre_esperado')->label('Cierre esperado'),
                TextInput::make('motivo_perdida')->label('Motivo de pérdida')
                    ->visible(fn ($get) => $get('etapa') === 'perdida')->required(fn ($get) => $get('etapa') === 'perdida'),
                Textarea::make('notas')->columnSpanFull()->rows(3),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['cliente', 'responsable']))
            ->defaultSort('cierre_esperado')
            ->columns([
                TextColumn::make('titulo')->searchable()->wrap(),
                TextColumn::make('nombre_prospecto')->label('Cliente / prospecto'),
                TextColumn::make('etapa')->badge()->formatStateUsing(fn ($s) => Oportunidad::ETAPAS[$s] ?? $s)
                    ->color(fn ($s) => match ($s) { 'ganada' => 'success', 'perdida' => 'danger', 'negociacion' => 'warning', default => 'gray' }),
                TextColumn::make('monto_estimado')->label('Monto')->money('HNL')->sortable(),
                TextColumn::make('probabilidad')->suffix('%'),
                TextColumn::make('responsable.name')->label('Responsable')->visible(fn () => self::esJefe()),
                TextColumn::make('cierre_esperado')->label('Cierre')->date('d/m/Y')->sortable(),
            ])
            ->filters([
            ])
            ->recordActions([
                Action::make('etapa')->label('Mover')->icon('heroicon-o-arrow-right-circle')
                    ->schema([
                        Select::make('etapa')->options(Oportunidad::ETAPAS)->required()->live(),
                        TextInput::make('motivo_perdida')->label('Motivo')->visible(fn ($get) => $get('etapa') === 'perdida')->required(fn ($get) => $get('etapa') === 'perdida'),
                    ])
                    ->fillForm(fn (Oportunidad $r) => ['etapa' => $r->etapa])
                    ->action(fn (Oportunidad $r, array $d) => $r->update(array_filter($d, fn ($v) => $v !== null))),
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [ActividadesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOportunidades::route('/'),
            'create' => CreateOportunidad::route('/create'),
            'edit' => EditOportunidad::route('/{record}/edit'),
        ];
    }
}
