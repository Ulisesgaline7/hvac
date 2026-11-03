<?php

namespace App\Filament\Resources\OrdenTrabajos\Tables;

use App\Enums\EstadoOt;
use App\Enums\TipoOt;
use App\Models\OrdenTrabajo;
use App\Models\Tecnico;
use App\Services\AgendaTecnico;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class OrdenTrabajosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fecha_programada')
            ->columns([
                TextColumn::make('numero')->label('OT')->searchable()->sortable(),
                TextColumn::make('tipo')->badge(),
                TextColumn::make('estado')->badge(),
                TextColumn::make('prioridad')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => OrdenTrabajo::PRIORIDADES[$state] ?? $state)
                    ->color(fn (?string $state) => match ($state) {
                        'emergencia' => 'danger',
                        'alta' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('cliente.razon_social')->label('Cliente')->searchable(),
                TextColumn::make('sucursal.nombre')->label('Sucursal'),
                TextColumn::make('tecnico.user.name')->label('Técnico')->placeholder('Sin asignar'),
                TextColumn::make('fecha_programada')->label('Programada')->date()->sortable(),
                TextColumn::make('hora_programada')->label('Hora')
                    ->formatStateUsing(fn ($state) => $state ? substr((string) $state, 0, 5) : '—')
                    ->toggleable(),
                TextColumn::make('sla')
                    ->label('SLA')
                    ->badge()
                    ->state(fn (OrdenTrabajo $record) => $record->estadoSla()[1])
                    ->color(fn (OrdenTrabajo $record) => $record->estadoSla()[2])
                    ->tooltip(fn (OrdenTrabajo $record) => $record->slaVence()?->format('d/m/Y H:i')),
                IconColumn::make('tardia')->label('Tardía')->boolean()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado')->options(EstadoOt::class)->multiple(),
                SelectFilter::make('tipo')->options(TipoOt::class),
                SelectFilter::make('prioridad')->options(OrdenTrabajo::PRIORIDADES),
                SelectFilter::make('tecnico_id')->label('Técnico')->relationship('tecnico.user', 'name'),
                Filter::make('atrasadas')
                    ->label('Atrasadas / SLA vencido')
                    ->toggle()
                    ->query(fn (Builder $q) => $q->atrasadas()),
                Filter::make('sin_asignar')
                    ->label('Sin técnico')
                    ->toggle()
                    ->query(fn (Builder $q) => $q->sinAsignar()),
                Filter::make('hoy')
                    ->label('Programadas hoy')
                    ->toggle()
                    ->query(fn (Builder $q) => $q->whereDate('fecha_programada', today())),
                Filter::make('programacion')
                    ->schema([
                        DatePicker::make('desde')->label('Programada desde'),
                        DatePicker::make('hasta')->label('Programada hasta'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['desde'] ?? null, fn ($q, $d) => $q->whereDate('fecha_programada', '>=', $d))
                        ->when($data['hasta'] ?? null, fn ($q, $d) => $q->whereDate('fecha_programada', '<=', $d))),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('informe')
                    ->label('Informe')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn ($record) => route('ordenes-trabajo.informe', $record->id), shouldOpenInNewTab: true),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::asignarMasivo(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    /** Asigna técnico, fecha y hora a varias OT, respetando choques de horario. */
    private static function asignarMasivo(): BulkAction
    {
        return BulkAction::make('asignar')
            ->label('Asignar técnico / fecha')
            ->icon('heroicon-o-user-plus')
            ->visible(fn () => (bool) auth()->user()?->hasAnyRole(['administrador', 'coordinador']))
            ->schema([
                Select::make('tecnico_id')
                    ->label('Técnico')
                    ->options(fn () => Tecnico::where('estado_homologacion', 'aprobado')->with('user')->get()->pluck('user.name', 'id'))
                    ->searchable()
                    ->required(),
                DatePicker::make('fecha')->label('Fecha (vacío = no cambiar)'),
                TimePicker::make('hora')->label('Hora (vacío = no cambiar)')->seconds(false),
                TextInput::make('duracion')->label('Duración estimada (min)')->numeric()->minValue(15)->maxValue(1440)->step(15),
            ])
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records, array $data) {
                $asignadas = 0;
                $omitidas = [];

                foreach ($records as $ot) {
                    /** @var OrdenTrabajo $ot */
                    if (! $ot->esAbierta()) {
                        $omitidas[] = "{$ot->numero}: está {$ot->estado->getLabel()}";

                        continue;
                    }

                    $fecha = filled($data['fecha'] ?? null) ? $data['fecha'] : $ot->fecha_programada?->toDateString();
                    $hora = filled($data['hora'] ?? null) ? $data['hora'] : $ot->hora_programada;
                    $duracion = filled($data['duracion'] ?? null) ? (int) $data['duracion'] : $ot->duracion_estimada_min;

                    if ($fecha && $hora) {
                        $choques = AgendaTecnico::choques((int) $data['tecnico_id'], $fecha, $hora, $duracion, $ot->id);

                        if ($choques->isNotEmpty()) {
                            $omitidas[] = "{$ot->numero}: choca con " . $choques->pluck('numero')->join(', ');

                            continue;
                        }
                    }

                    try {
                        DB::transaction(function () use ($ot, $data) {
                            $ot->tecnico_id = $data['tecnico_id'];

                            if (filled($data['fecha'] ?? null)) {
                                if ($ot->fecha_programada && $ot->fecha_programada->toDateString() !== $data['fecha']) {
                                    $ot->fecha_original ??= $ot->fecha_programada;
                                }
                                $ot->fecha_programada = $data['fecha'];
                            }
                            if (filled($data['hora'] ?? null)) {
                                $ot->hora_programada = $data['hora'];
                            }
                            if (filled($data['duracion'] ?? null)) {
                                $ot->duracion_estimada_min = (int) $data['duracion'];
                            }

                            $ot->save();

                            if ($ot->estado === EstadoOt::Pendiente) {
                                $ot->cambiarEstado(EstadoOt::Asignada, 'Asignación masiva');
                            }
                        });

                        $asignadas++;
                    } catch (\DomainException $e) {
                        $omitidas[] = "{$ot->numero}: " . $e->getMessage();
                    }
                }

                if ($asignadas > 0) {
                    Notification::make()->title("{$asignadas} OT asignadas")->success()->send();
                }

                if ($omitidas) {
                    $detalle = collect($omitidas)->take(8)->map(fn ($l) => e($l))->join('<br>');

                    Notification::make()
                        ->title(count($omitidas) . ' OT no se asignaron')
                        ->body(new HtmlString($detalle))
                        ->warning()
                        ->persistent()
                        ->send();
                }
            });
    }
}
