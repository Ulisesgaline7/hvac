<?php

namespace App\Filament\Resources\Cotizacions\Pages;

use App\Filament\Resources\Cotizacions\CotizacionResource;
use App\Services\CotizacionFlujo;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCotizacion extends EditRecord
{
    protected static string $resource = CotizacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')->label('PDF')->icon('heroicon-o-document-arrow-down')->color('gray')
                ->url(fn () => route('cotizaciones.pdf', $this->record))->openUrlInNewTab(),

            Action::make('enviar')->label('Marcar como enviada')->icon('heroicon-o-paper-airplane')
                ->visible(fn () => $this->record->estado === 'borrador')
                ->requiresConfirmation()
                ->modalDescription('Después de enviarla ya no se podrán editar las líneas. Guarde antes los cambios pendientes.')
                ->action(fn () => $this->ejecutar(function () {
                    app(CotizacionFlujo::class)->enviar($this->record);

                    return null;
                }, 'Cotización enviada')),

            Action::make('enlace')->label('Enlace para el cliente')->icon('heroicon-o-link')->color('gray')
                ->visible(fn () => $this->record->estado === 'enviada' && filled($this->record->token_aceptacion))
                ->modalHeading('Enlace de aceptación digital')
                ->modalDescription('Envíe este enlace al cliente. Podrá revisar, firmar y aceptar o rechazar. Deja de funcionar cuando responda o venza la cotización.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar')
                ->schema(fn () => [
                    TextInput::make('url')->label('Enlace')->readOnly()->extraInputAttributes(['onclick' => 'this.select()'])
                        ->default(route('cotizaciones.aceptacion', $this->record->token_aceptacion))
                        ->dehydrated(false),
                ]),
            Action::make('aprobar')->label('Registrar aprobación')->icon('heroicon-o-check-circle')->color('success')
                ->visible(fn () => $this->record->estado === 'enviada')
                ->modalDescription('Registre la aprobación recibida del cliente. Se creará el contrato en borrador o las órdenes de trabajo que correspondan.')
                ->schema([
                    TextInput::make('aprobada_por')->label('Nombre de quien aprueba (cliente)')->required(),
                    DatePicker::make('fecha_programada')->label('Fecha programada del trabajo')
                        ->minDate(now()->startOfDay())->helperText('Opcional. Si la deja vacía, la OT queda sin fecha para que el coordinador la programe.'),
                ])
                ->action(fn (array $data) => $this->ejecutar(function () use ($data) {
                    $r = app(CotizacionFlujo::class)->aprobar($this->record, $data['aprobada_por'], $data['fecha_programada'] ?? null);

                    $partes = [];
                    if ($r['contrato']) {
                        $partes[] = 'Contrato en borrador #' . $r['contrato']->id;
                    }
                    foreach ($r['ordenes'] as $ot) {
                        $partes[] = 'OT ' . ($ot->numero ?? $ot->id);
                    }

                    return $partes ? implode(' · ', $partes) : 'No había contrato ni OT que generar.';
                }, 'Cotización aprobada')),

            Action::make('rechazar')->label('Registrar rechazo')->icon('heroicon-o-x-circle')->color('danger')
                ->visible(fn () => $this->record->estado === 'enviada')
                ->schema([Textarea::make('motivo')->label('Motivo')])
                ->action(fn (array $data) => $this->ejecutar(function () use ($data) {
                    app(CotizacionFlujo::class)->rechazar($this->record, $data['motivo'] ?? null);

                    return null;
                }, 'Cotización rechazada')),

            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    private function ejecutar(callable $accion, string $titulo): void
    {
        try {
            $detalle = $accion();
        } catch (DomainException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            return;
        }

        $this->record->refresh();
        $this->refreshFormData(['estado', 'notas', 'subtotal', 'isv', 'total']);

        Notification::make()->success()->title($titulo)->body($detalle)->send();
    }
}
