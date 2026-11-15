<?php

namespace App\Filament\Resources\Colaboradores\Pages;

use App\Filament\Resources\Colaboradores\ColaboradorResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EditColaborador extends EditRecord
{
    protected static string $resource = ColaboradorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cambiar_vinculo')
                ->label(fn () => $this->record->vinculoActivo ? 'Cambiar vínculo' : 'Registrar vínculo')
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->modalHeading('Cambiar vínculo (por ejemplo, convertir en empleado)')
                ->modalDescription('El vínculo actual se cierra el día anterior a la fecha indicada y se abre el nuevo. El historial se conserva.')
                ->schema([
                    Select::make('tipo')->options(['empleado' => 'Empleado', 'independiente' => 'Independiente', 'externo' => 'Externo'])
                        ->default('empleado')->required(),
                    TextInput::make('cargo')->required()
                        ->default(fn () => $this->record->vinculoActivo?->cargo),
                    DatePicker::make('desde')->label('Vigente desde')->required()->default(now())
                        ->minDate(fn () => $this->record->vinculoActivo?->fecha_inicio),
                    Select::make('remuneracion')->label('Remuneración')
                        ->options(['tarifa' => 'Tarifa por servicio', 'salario' => 'Salario mensual']),
                    TextInput::make('monto')->label('Monto (L)')->numeric()->minValue(0),
                    TextInput::make('comision_pct')->label('Comisión (%)')->numeric()->minValue(0)->maxValue(100),
                    TextInput::make('motivo')->label('Motivo del cambio')->default('Cambio de vínculo'),
                ])
                ->action(function (array $data) {
                    try {
                        $this->record->cambiarVinculo(
                            $data['tipo'],
                            $data['cargo'],
                            Carbon::parse($data['desde']),
                            $data['remuneracion'] ?? null,
                            filled($data['monto'] ?? null) ? (float) $data['monto'] : null,
                            filled($data['comision_pct'] ?? null) ? (float) $data['comision_pct'] : null,
                            $data['motivo'] ?? null,
                        );
                        $this->record->update(['estado' => 'activo']);
                    } catch (\DomainException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();

                        return;
                    }

                    Notification::make()->title('Vínculo actualizado')->success()->send();
                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->record]));
                }),

            Action::make('terminar_vinculo')
                ->label('Terminar vínculo')
                ->icon(Heroicon::OutlinedUserMinus)
                ->color('danger')
                ->visible(fn () => (bool) $this->record->vinculoActivo)
                ->requiresConfirmation()
                ->schema([
                    DatePicker::make('fecha_fin')->label('Fecha de fin')->required()->default(now())
                        ->minDate(fn () => $this->record->vinculoActivo?->fecha_inicio),
                    TextInput::make('motivo')->label('Motivo')->required(),
                ])
                ->action(function (array $data) {
                    DB::transaction(function () use ($data) {
                        $this->record->vinculoActivo->update([
                            'fecha_fin' => $data['fecha_fin'],
                            'motivo_fin' => $data['motivo'],
                        ]);
                        $this->record->update(['estado' => 'inactivo']);
                    });

                    Notification::make()->title('Vínculo terminado')->success()->send();
                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->record]));
                }),

            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
