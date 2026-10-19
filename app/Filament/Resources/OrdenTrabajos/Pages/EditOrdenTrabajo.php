<?php

namespace App\Filament\Resources\OrdenTrabajos\Pages;

use App\Enums\EstadoOt;
use App\Filament\Resources\OrdenTrabajos\OrdenTrabajoResource;
use App\Models\Informe;
use App\Models\Tecnico;
use App\Services\PublicadorInforme;
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
            Action::make('generarInforme')
                ->label('Generar informe')
                ->icon('heroicon-o-document-plus')
                ->visible(fn () => $this->record->estado === EstadoOt::Cerrada
                    && ! Informe::where('orden_trabajo_id', $this->record->id)->where('estado', 'publicado')->exists()
                    && (auth()->user()?->hasAnyRole(['administrador', 'coordinador']) ?? false))
                ->requiresConfirmation()
                ->modalDescription('Se genera el PDF en borrador. El cliente no lo verá hasta que lo publiques.')
                ->action(function () {
                    try {
                        app(PublicadorInforme::class)->generar($this->record);
                    } catch (\DomainException $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();

                        return;
                    }

                    Notification::make()->success()->title('Informe generado (borrador)')->send();
                }),
            Action::make('publicarInforme')
                ->label('Publicar informe')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn () => (auth()->user()?->hasAnyRole(['administrador', 'coordinador']) ?? false)
                    && Informe::where('orden_trabajo_id', $this->record->id)->where('estado', 'borrador')->exists())
                ->requiresConfirmation()
                ->modalDescription('Revisa el PDF antes. Al publicarlo, el cliente podrá verlo y descargarlo, y ya no se podrá regenerar.')
                ->action(function () {
                    try {
                        $informe = Informe::where('orden_trabajo_id', $this->record->id)->firstOrFail();
                        app(PublicadorInforme::class)->publicar($informe);
                    } catch (\DomainException $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();

                        return;
                    }

                    Notification::make()->success()->title('Informe publicado')->send();
                }),
            Action::make('cotizarVisita')
                ->label('Crear cotización desde visita')
                ->icon('heroicon-o-document-currency-dollar')
                ->color('success')
                ->visible(fn () => $this->record->tipo === \App\Enums\TipoOt::VisitaTecnica
                    && $this->record->estado === EstadoOt::Cerrada
                    && ! \App\Models\Cotizacion::where('visita_ot_id', $this->record->id)->exists()
                    && (auth()->user()?->hasAnyRole(['administrador', 'coordinador']) ?? false))
                ->requiresConfirmation()
                ->modalDescription('Se crea una cotización en borrador con el cliente y la sucursal de la visita. Agregue las líneas; el costo de la visita se calcula según la regla.')
                ->action(function () {
                    $ot = $this->record;
                    $c = \App\Models\Cotizacion::create([
                        'cliente_id' => $ot->cliente_id,
                        'sucursal_id' => $ot->sucursal_id,
                        'vendedor_id' => auth()->id(),
                        'visita_ot_id' => $ot->id,
                        'notas' => trim("Visita {$ot->numero}\nDiagnóstico: {$ot->diagnostico}\nRecomendaciones: {$ot->recomendaciones}"),
                    ]);

                    $this->redirect(\App\Filament\Resources\Cotizacions\CotizacionResource::getUrl('edit', ['record' => $c]));
                }),
            Action::make('registrarAnticipo')
                ->label('Registrar anticipo')
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->visible(fn () => ! in_array($this->record->estado, [EstadoOt::Cerrada, EstadoOt::Cancelada], true)
                    && ! in_array($this->record->tipo, [\App\Enums\TipoOt::Preventivo, \App\Enums\TipoOt::VisitaTecnica], true)
                    && (auth()->user()?->hasAnyRole(['administrador', 'coordinador']) ?? false))
                ->modalDescription(fn () => 'Ya cobrado como anticipo: L ' . number_format((float) \App\Models\Pago::where('orden_trabajo_id', $this->record->id)->where('tipo', 'anticipo')->sum('monto'), 2))
                ->schema(fn () => [
                    \Filament\Forms\Components\TextInput::make('monto')->label('Monto (L)')->numeric()->required()->minValue(0.01)
                        ->default(fn () => app(\App\Services\FacturacionService::class)->anticipoSugerido($this->record) ?: null)
                        ->helperText('Sugerido según config/cobros.php.'),
                    \Filament\Forms\Components\Select::make('metodo')->label('Método')->options([
                        'efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'cheque' => 'Cheque', 'tarjeta' => 'Tarjeta',
                    ])->required(),
                    \Filament\Forms\Components\TextInput::make('referencia')->label('Referencia / comprobante'),
                ])
                ->action(function (array $data) {
                    $pago = app(\App\Services\FacturacionService::class)->registrarAnticipo(
                        $this->record, (float) $data['monto'], $data['metodo'], $data['referencia'] ?? null
                    );
                    Notification::make()->success()->title('Anticipo registrado')
                        ->body("Comprobante {$pago->fresh()->numero}")
                        ->actions([
                            \Filament\Actions\Action::make('comprobante')
                                ->label('Ver comprobante')
                                ->url(route('pagos.recibo', $pago->id))
                                ->openUrlInNewTab(),
                        ])
                        ->send();
                }),
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
