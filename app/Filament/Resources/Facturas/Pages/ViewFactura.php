<?php

namespace App\Filament\Resources\Facturas\Pages;

use App\Filament\Resources\Facturas\FacturaResource;
use App\Filament\Resources\Facturas\Tables\FacturasTable;
use App\Services\PlanCuotasService;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewFactura extends ViewRecord
{
    protected static string $resource = FacturaResource::class;

    protected function getHeaderActions(): array
    {
        $interno = fn () => auth()->user()?->hasAnyRole(['administrador', 'coordinador']) ?? false;

        return [
            FacturasTable::accionRegistrarPago(),

            Action::make('planCuotas')
                ->label('Plan de cuotas')
                ->icon('heroicon-o-calendar-days')
                ->visible(fn () => $interno()
                    && ! in_array($this->record->estado, ['pagada', 'anulada'], true)
                    && ! $this->record->cuotas()->exists())
                ->modalDescription(fn () => 'Se divide el total con ISV: L ' . number_format((float) $this->record->total, 2))
                ->schema([
                    Select::make('cuotas')->label('Número de cuotas')
                        ->options(array_combine(range(2, 12), range(2, 12)))->default(3)->required(),
                    DatePicker::make('primer_vence')->label('Primer vencimiento')
                        ->helperText('Vacío = fecha de vencimiento de la factura. Las demás vencen cada mes.'),
                ])
                ->action(function (array $data) {
                    try {
                        $cuotas = app(PlanCuotasService::class)->crear(
                            $this->record,
                            (int) $data['cuotas'],
                            filled($data['primer_vence'] ?? null) ? \Carbon\Carbon::parse($data['primer_vence']) : null,
                        );
                    } catch (DomainException $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();

                        return;
                    }

                    Notification::make()->success()->title("Plan creado: {$cuotas->count()} cuotas")->send();
                }),

            Action::make('verPlan')
                ->label('Ver plan de cuotas')
                ->icon('heroicon-o-calendar-days')
                ->color('gray')
                ->visible(fn () => $this->record->cuotas()->exists())
                ->modalHeading('Plan de cuotas')
                ->modalContent(fn () => view('facturas.plan-cuotas', [
                    'filas' => app(PlanCuotasService::class)->estado($this->record->fresh()),
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar'),

            Action::make('quitarPlan')
                ->label('Quitar plan')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Se borra el calendario de cuotas. Los pagos ya registrados no se tocan.')
                ->visible(fn () => $interno() && $this->record->cuotas()->exists())
                ->action(function () {
                    app(PlanCuotasService::class)->quitar($this->record);
                    Notification::make()->success()->title('Plan de cuotas eliminado')->send();
                }),
        ];
    }
}
