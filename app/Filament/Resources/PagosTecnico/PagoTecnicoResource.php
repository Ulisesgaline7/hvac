<?php

namespace App\Filament\Resources\PagosTecnico;

use App\Filament\Resources\PagosTecnico\Pages\ListPagosTecnico;
use App\Models\PagoTecnico;
use App\Models\Tecnico;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class PagoTecnicoResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Finanzas';

    protected static ?int $navigationSort = 3;

    protected static ?string $model = PagoTecnico::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $slug = 'pagos-tecnico';
    protected static ?string $modelLabel = 'pago a técnico';
    protected static ?string $pluralModelLabel = 'Pagos a técnicos';

    private const ESTADOS = [
        'pendiente' => 'Pendiente',
        'aprobado' => 'Aprobado',
        'liquidado' => 'Liquidado',
        'sin_tarifa' => 'Sin tarifa',
        'retorno' => 'Retorno (revisar)',
        'anulado' => 'Anulado',
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

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('ordenTrabajo.numero')->label('OT')->searchable()->sortable(),
                TextColumn::make('tecnico.user.name')->label('Técnico')->searchable(),
                TextColumn::make('rol')->label('Rol')->badge(),
                TextColumn::make('concepto')->label('Concepto')->wrap()->searchable(),
                TextColumn::make('porcentaje')->label('%')->suffix(' %'),
                TextColumn::make('monto')->label('Monto')->money('HNL')->sortable(),
                TextColumn::make('estado')->label('Estado')->badge()
                    ->formatStateUsing(fn (string $state) => self::ESTADOS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'aprobado' => 'info',
                        'liquidado' => 'success',
                        'sin_tarifa', 'retorno' => 'warning',
                        'anulado' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('aprobado_en')->label('Aprobado')->dateTime('d/m/Y')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('estado')->label('Estado')->options(self::ESTADOS),
                SelectFilter::make('tecnico_id')->label('Técnico')
                    ->options(fn () => Tecnico::with('user')->get()->pluck('user.name', 'id')->all()),
            ])
            ->recordActions([
                Action::make('aprobar')->label('Aprobar')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (PagoTecnico $r) => $r->estado === 'pendiente')
                    ->requiresConfirmation()
                    ->action(fn (PagoTecnico $r) => $r->update(['estado' => 'aprobado', 'aprobado_en' => now()])),
                Action::make('fijarMonto')->label('Fijar monto')->icon('heroicon-o-pencil-square')->color('warning')
                    ->visible(fn (PagoTecnico $r) => in_array($r->estado, ['sin_tarifa', 'retorno', 'pendiente'], true) && ! $r->liquidacion_id)
                    ->form([TextInput::make('monto')->label('Monto a pagar (L)')->numeric()->prefix('L')->minValue(0)->required()])
                    ->fillForm(fn (PagoTecnico $r) => ['monto' => $r->monto])
                    ->action(function (PagoTecnico $r, array $data) {
                        $monto = round((float) $data['monto'], 2);
                        $r->update([
                            'monto' => $monto,
                            'monto_base' => $r->porcentaje > 0 ? round($monto * 100 / (float) $r->porcentaje, 2) : $monto,
                            'estado' => $monto > 0 ? 'pendiente' : $r->estado,
                        ]);
                        Notification::make()->success()->title('Monto actualizado. Falta aprobarlo.')->send();
                    }),
                Action::make('anular')->label('Anular')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (PagoTecnico $r) => in_array($r->estado, ['pendiente', 'aprobado', 'sin_tarifa', 'retorno'], true) && ! $r->liquidacion_id)
                    ->requiresConfirmation()
                    ->action(fn (PagoTecnico $r) => $r->update(['estado' => 'anulado', 'aprobado_en' => null])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('aprobarVarios')->label('Aprobar seleccionados')->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $n = 0;
                            foreach ($records as $r) {
                                if ($r->estado === 'pendiente') {
                                    $r->update(['estado' => 'aprobado', 'aprobado_en' => now()]);
                                    $n++;
                                }
                            }
                            Notification::make()->success()->title("Pagos aprobados: {$n}")->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPagosTecnico::route('/')];
    }
}
