<?php

namespace App\Filament\Resources\LiquidacionesTecnico;

use App\Filament\Resources\LiquidacionesTecnico\Pages\ListLiquidacionesTecnico;
use App\Models\LiquidacionTecnico;
use App\Models\Tecnico;
use App\Services\LiquidacionService;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LiquidacionTecnicoResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Finanzas';

    protected static ?int $navigationSort = 4;

    protected static ?string $model = LiquidacionTecnico::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $slug = 'liquidaciones-tecnico';
    protected static ?string $modelLabel = 'liquidación';
    protected static ?string $pluralModelLabel = 'Liquidaciones de técnicos';

    private const ESTADOS = [
        'borrador' => 'Borrador',
        'pagada' => 'Pagada',
        'anulada' => 'Anulada',
    ];

    public static function getNavigationGroup(): ?string
    {
        return 'Operación';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['administrador', 'coordinador']) ?? false;
    }

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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('tecnico.user')->withCount('pagos');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('numero')->label('Número')->placeholder('Sin emitir')->searchable()->sortable(),
                TextColumn::make('tecnico.user.name')->label('Técnico')->searchable(),
                TextColumn::make('semana_inicio')->label('Semana')->date('d/m/Y')
                    ->description(fn (LiquidacionTecnico $r) => 'al ' . $r->semana_fin->format('d/m/Y'))->sortable(),
                TextColumn::make('pagos_count')->label('Pagos'),
                TextColumn::make('bruto')->label('Bruto')->money('HNL'),
                TextColumn::make('retencion')->label('Retención')->money('HNL')->toggleable(),
                TextColumn::make('neto')->label('Neto')->money('HNL')->sortable(),
                TextColumn::make('estado')->label('Estado')->badge()
                    ->formatStateUsing(fn (string $state) => self::ESTADOS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'pagada' => 'success',
                        'anulada' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('pagada_en')->label('Pagada')->dateTime('d/m/Y')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('estado')->label('Estado')->options(self::ESTADOS),
                SelectFilter::make('tecnico_id')->label('Técnico')
                    ->options(fn () => Tecnico::with('user')->get()->pluck('user.name', 'id')->all()),
            ])
            ->recordActions([
                Action::make('detalle')->label('Detalle')->icon('heroicon-o-eye')->color('gray')
                    ->modalHeading(fn (LiquidacionTecnico $r) => 'Detalle · ' . ($r->numero ?: 'borrador'))
                    ->modalContent(fn (LiquidacionTecnico $r) => view('filament.liquidacion-detalle', ['liq' => $r->load('pagos.ordenTrabajo')]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
                Action::make('pagar')->label('Marcar pagada')->icon('heroicon-o-check-badge')->color('success')
                    ->visible(fn (LiquidacionTecnico $r) => $r->estado === LiquidacionService::BORRADOR)
                    ->modalHeading('Marcar como pagada')
                    ->modalDescription('Se asigna el número RT y ya no se podrá editar. Si hay un error, se anula y se emite otra.')
                    ->fillForm(fn (LiquidacionTecnico $r) => ['recibe_nombre' => $r->tecnico?->user?->name])
                    ->schema([
                        Select::make('metodo')->label('Método')->options([
                            'efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'cheque' => 'Cheque',
                        ])->required(),
                        TextInput::make('referencia')->label('Referencia'),
                        TextInput::make('recibe_nombre')->label('Nombre de quien recibe')->required()->maxLength(255),
                    ])
                    ->action(function (LiquidacionTecnico $r, array $data) {
                        try {
                            $liq = app(LiquidacionService::class)->pagar(
                                $r, $data['metodo'], $data['referencia'] ?? null, $data['recibe_nombre'], (int) auth()->id()
                            );
                        } catch (DomainException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();

                            return;
                        }
                        Notification::make()->success()->title("Pagada · {$liq->numero}")
                            ->actions([
                                Action::make('comprobante')->label('Ver comprobante')
                                    ->url(route('liquidaciones.comprobante', $liq->id))->openUrlInNewTab(),
                            ])
                            ->send();
                    }),
                Action::make('comprobante')->label('Comprobante')->icon('heroicon-o-document-text')
                    ->visible(fn (LiquidacionTecnico $r) => filled($r->numero))
                    ->url(fn (LiquidacionTecnico $r) => route('liquidaciones.comprobante', $r->id))
                    ->openUrlInNewTab(),
                Action::make('anular')->label('Anular')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (LiquidacionTecnico $r) => $r->estado !== LiquidacionService::ANULADA)
                    ->requiresConfirmation()
                    ->modalHeading('Anular liquidación')
                    ->modalDescription('Los pagos quedan libres para volver a liquidarlos. El número emitido no se reutiliza.')
                    ->schema([Textarea::make('motivo')->label('Motivo')->required()])
                    ->action(function (LiquidacionTecnico $r, array $data) {
                        try {
                            app(LiquidacionService::class)->anular($r, $data['motivo']);
                        } catch (DomainException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();

                            return;
                        }
                        Notification::make()->success()->title('Liquidación anulada')->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListLiquidacionesTecnico::route('/')];
    }
}
