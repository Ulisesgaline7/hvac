<?php

namespace App\Filament\Resources\OrdenTrabajos\Pages;

use App\Enums\EstadoOt;
use App\Filament\Resources\OrdenTrabajos\OrdenTrabajoResource;
use App\Models\Tecnico;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;

class EditOrdenTrabajo extends EditRecord
{
    protected static string $resource = OrdenTrabajoResource::class;

    private const CAMPOS = ['estado', 'tecnico_id', 'fecha_programada', 'fecha_original', 'motivo_reprogramacion', 'motivo_devolucion'];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('informe')
                ->label('Informe PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn () => route('ordenes-trabajo.informe', $this->record->id), shouldOpenInNewTab: true),
            Action::make('asignar')
                ->label('Asignar técnico')
                ->icon('heroicon-o-user-plus')
                ->visible(fn () => in_array($this->record->estado, [EstadoOt::Pendiente, EstadoOt::Asignada], true))
                ->schema([
                    Select::make('tecnico_id')
                        ->label('Técnico aprobado')
                        ->options(fn () => Tecnico::where('estado_homologacion', 'aprobado')->with('user')->get()->pluck('user.name', 'id'))
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->tecnico_id = $data['tecnico_id'];

                    if ($this->record->estado === EstadoOt::Pendiente) {
                        $this->record->cambiarEstado(EstadoOt::Asignada, 'Asignación de técnico');
                    } else {
                        $this->record->save();
                    }

                    $this->refreshFormData(self::CAMPOS);
                    Notification::make()->success()->title('Técnico asignado')->send();
                }),
            Action::make('reprogramar')
                ->label('Reprogramar')
                ->icon('heroicon-o-calendar')
                ->visible(fn () => in_array($this->record->estado, [EstadoOt::Pendiente, EstadoOt::Asignada, EstadoOt::EnEspera], true))
                ->schema([
                    DatePicker::make('fecha')->label('Nueva fecha')->required(),
                    Textarea::make('motivo')->label('Motivo')->required(),
                ])
                ->action(function (array $data) {
                    $this->record->fecha_original ??= $this->record->fecha_programada;
                    $this->record->fecha_programada = $data['fecha'];
                    $this->record->motivo_reprogramacion = $data['motivo'];
                    $this->record->save();

                    $this->refreshFormData(self::CAMPOS);
                    Notification::make()->success()->title('OT reprogramada')->send();
                }),
            Action::make('cambiarEstado')
                ->label('Cambiar estado')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn () => count($this->record->estado->transiciones()) > 0)
                ->schema([
                    Select::make('estado')
                        ->label('Nuevo estado')
                        ->options(fn () => collect($this->record->estado->transiciones())
                            ->mapWithKeys(fn (EstadoOt $e) => [$e->value => $e->getLabel()]))
                        ->required()
                        ->live(),
                    Textarea::make('motivo')
                        ->label('Motivo')
                        ->required(fn (Get $get) => in_array($get('estado'), [EstadoOt::Cancelada->value, EstadoOt::EnProceso->value, EstadoOt::EnEspera->value], true)),
                ])
                ->action(function (array $data) {
                    try {
                        $this->record->cambiarEstado(EstadoOt::from($data['estado']), $data['motivo'] ?? null);
                    } catch (\DomainException $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();

                        return;
                    }

                    $this->refreshFormData(self::CAMPOS);
                    $this->dispatch('$refresh');
                    Notification::make()->success()->title('Estado actualizado')->send();
                }),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
