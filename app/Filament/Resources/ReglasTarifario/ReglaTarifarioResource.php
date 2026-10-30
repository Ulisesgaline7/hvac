<?php

namespace App\Filament\Resources\ReglasTarifario;

use App\Filament\Resources\ReglasTarifario\Pages\ManageReglasTarifario;
use App\Models\ReglaTarifario;
use BackedEnum;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ReglaTarifarioResource extends Resource
{
    protected static ?string $model = ReglaTarifario::class;

    protected static ?string $slug = 'reglas-tarifario';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Tarifario';

    protected static ?string $navigationLabel = 'Reglas comerciales';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'regla';

    protected static ?string $pluralModelLabel = 'reglas comerciales';

    protected static ?string $recordTitleAttribute = 'etiqueta';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    protected static function esTipo(string ...$tipos): Closure
    {
        return fn (?ReglaTarifario $record): bool => in_array($record?->tipo, $tipos, true);
    }

    /** Se guarda como fracción (0.15) pero se edita como porcentaje (15). */
    protected static function pct(string $name, string $label): TextInput
    {
        return TextInput::make($name)->label($label)->numeric()->required()->minValue(0)->maxValue(100)->suffix('%')
            ->formatStateUsing(fn ($state) => $state === null || $state === '' ? null : round((float) $state * 100, 2))
            ->dehydrateStateUsing(fn ($state) => round((float) $state / 100, 4));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('etiqueta')->label('Regla')->disabled()->dehydrated(false),

            static::pct('valor', 'Porcentaje')->visible(static::esTipo('pct'))
                ->helperText(fn (?ReglaTarifario $record) => $record?->ayuda),

            TextInput::make('valor')->label('Monto')->numeric()->required()->minValue(0)->prefix('L')
                ->visible(static::esTipo('monto'))
                ->helperText(fn (?ReglaTarifario $record) => $record?->ayuda),

            TextInput::make('valor')->label('Valor')->numeric()->integer()->required()->minValue(0)
                ->visible(static::esTipo('entero'))
                ->helperText(fn (?ReglaTarifario $record) => $record?->ayuda),

            Repeater::make('valor')->label('Recargos')->visible(static::esTipo('recargos'))
                ->helperText(fn (?ReglaTarifario $record) => $record?->ayuda)
                ->columns(3)->collapsible()->reorderable()->addActionLabel('Agregar recargo')
                ->itemLabel(fn (array $state): ?string => $state['nombre'] ?? null)
                ->schema([
                    TextInput::make('nombre')->required()->columnSpanFull(),
                    TextInput::make('codigo')->label('Código')->required()->alphaDash()->distinct()
                        ->dehydrateStateUsing(fn ($state) => strtoupper(trim((string) $state)))
                        ->helperText('No lo cambies si ya se usó en cotizaciones.'),
                    static::pct('min', 'Mínimo'),
                    static::pct('max', 'Máximo'),
                ]),

            Repeater::make('valor')->label('Descuentos')->visible(static::esTipo('descuentos'))
                ->helperText(fn (?ReglaTarifario $record) => $record?->ayuda)
                ->columns(4)->collapsible()->reorderable()->addActionLabel('Agregar descuento')
                ->itemLabel(fn (array $state): ?string => $state['nombre'] ?? null)
                ->schema([
                    TextInput::make('nombre')->required()->columnSpanFull(),
                    TextInput::make('codigo')->label('Código')->required()->alphaDash()->distinct()
                        ->dehydrateStateUsing(fn ($state) => strtoupper(trim((string) $state)))
                        ->helperText('No lo cambies si ya se usó.'),
                    static::pct('min', 'Mínimo'),
                    static::pct('max', 'Máximo'),
                    TextInput::make('desde')->label('Desde (equipos)')->numeric()->integer()->minValue(1)
                        ->helperText('Solo descuentos por volumen.'),
                ]),

            Repeater::make('valor')->label('Categorías de garantía')->visible(static::esTipo('categorias'))
                ->helperText(fn (?ReglaTarifario $record) => $record?->ayuda)
                ->columns(2)->collapsible()->reorderable()->addActionLabel('Agregar categoría')
                ->itemLabel(fn (array $state): ?string => $state['clave'] ?? null)
                ->schema([
                    TextInput::make('clave')->required()->alphaDash()->distinct()
                        ->dehydrateStateUsing(fn ($state) => strtolower(trim((string) $state)))
                        ->helperText('No la cambies si ya hay garantías o servicios con esta categoría.'),
                    TextInput::make('dias')->label('Días de garantía')->numeric()->integer()->required()->minValue(0),
                    Textarea::make('corrige')->label('Quién corrige')->rows(2)->required()->columnSpanFull(),
                    Textarea::make('cubre')->label('Qué cubre')->rows(2)->required(),
                    Textarea::make('no_cubre')->label('Qué no cubre')->rows(2)->required(),
                ]),

            Repeater::make('valor')->label('Condiciones')->visible(static::esTipo('condiciones'))
                ->helperText(fn (?ReglaTarifario $record) => $record?->ayuda)
                ->reorderable()->addActionLabel('Agregar condición')
                ->simple(Textarea::make('texto')->label('Condición')->rows(2)->required()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('grupo')->label('Sección')->badge(),
                TextColumn::make('etiqueta')->label('Regla')->wrap(),
                TextColumn::make('resumen')->label('Valor actual')
                    ->getStateUsing(fn (ReglaTarifario $record) => $record->resumen())->wrap(),
            ])
            ->defaultSort('orden')
            ->paginated(false)
            ->recordActions([EditAction::make()->modalWidth('4xl')]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageReglasTarifario::route('/')];
    }
}
