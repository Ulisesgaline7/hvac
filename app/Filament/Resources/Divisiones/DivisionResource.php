<?php

namespace App\Filament\Resources\Divisiones;

use App\Filament\Resources\Divisiones\Pages\ManageDivisiones;
use App\Models\Division;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DivisionResource extends Resource
{
    protected static ?string $model = Division::class;

    protected static ?string $slug = 'divisiones';

    protected static ?string $modelLabel = 'división';

    protected static ?string $pluralModelLabel = 'divisiones';

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 20;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    private static function permitido(): bool
    {
        return auth()->user()?->hasRole('administrador') ?? false;
    }

    public static function canViewAny(): bool { return self::permitido(); }
    public static function canCreate(): bool { return self::permitido(); }
    public static function canEdit(Model $record): bool { return self::permitido(); }
    public static function canDelete(Model $record): bool { return false; } // se desactiva, no se borra

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('codigo')->label('Código')->required()->maxLength(20)
                ->unique(ignoreRecord: true)
                ->disabled(fn (string $operation) => $operation === 'edit'),
            TextInput::make('nombre')->required()->maxLength(255),
            Textarea::make('descripcion')->label('Descripción')->columnSpanFull(),
            Select::make('etapa')->options([
                1 => 'Etapa 1 · prioritaria',
                2 => 'Etapa 2 · expansión técnica',
                3 => 'Etapa 3 · complementarias',
                4 => 'Etapa 4 · servicios integrales',
            ])->default(1)->required(),
            Toggle::make('activa')->helperText('Las divisiones inactivas no se ofrecerán en OT ni cotizaciones.'),
            Select::make('responsable_id')->label('Responsable')->searchable()
                ->options(fn () => User::whereNull('cliente_id')->orderBy('name')->pluck('name', 'id')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('orden')
            ->columns([
                TextColumn::make('etapa')->badge()->formatStateUsing(fn ($state) => 'Etapa ' . $state),
                TextColumn::make('codigo')->label('Código')->searchable(),
                TextColumn::make('nombre')->searchable(),
                IconColumn::make('activa')->boolean(),
                TextColumn::make('responsable.name')->label('Responsable')->placeholder('—'),
            ])
            ->recordActions([
                EditAction::make(),
                \Filament\Actions\Action::make('copiar_clima')
                    ->label('Copiar base de CLIMA')
                    ->icon('heroicon-o-document-duplicate')
                    ->visible(fn ($record) => $record->codigo !== 'CLIMA')
                    ->requiresConfirmation()
                    ->modalDescription('Copia las reglas del tarifario y los servicios de CLIMA a esta división. Los servicios quedan inactivos hasta que revises los precios.')
                    ->action(function ($record) {
                        try {
                            [$r, $s] = \App\Services\DivisionService::copiarDesdeClima($record);
                            \Filament\Notifications\Notification::make()->title("Copiadas {$r} reglas y {$s} servicios (inactivos)")->success()->send();
                        } catch (\DomainException $e) {
                            \Filament\Notifications\Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageDivisiones::route('/')];
    }
}
