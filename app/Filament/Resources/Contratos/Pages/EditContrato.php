<?php

namespace App\Filament\Resources\Contratos\Pages;

use App\Enums\EstadoContrato;
use App\Filament\Resources\Contratos\ContratoResource;
use App\Services\GeneradorCalendario;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditContrato extends EditRecord
{
    protected static string $resource = ContratoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('activar')
                ->label('Activar y generar calendario')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn () => $this->record->estado === EstadoContrato::Borrador)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['estado' => EstadoContrato::Activo]);
                    $creadas = app(GeneradorCalendario::class)->generar($this->record->fresh());
                    $this->refreshFormData(['estado']);
                    Notification::make()->success()->title("Contrato activado. OT creadas: $creadas")->send();
                }),
            Action::make('cancelar')
                ->label('Cancelar contrato')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->estado === EstadoContrato::Activo)
                ->requiresConfirmation()
                ->modalDescription('Se cancelan las OT preventivas pendientes futuras. Las iniciadas y cerradas no se tocan.')
                ->action(function () {
                    $this->record->update(['estado' => EstadoContrato::Cancelado]);
                    $r = app(GeneradorCalendario::class)->recalcular($this->record->fresh());
                    $this->refreshFormData(['estado']);
                    Notification::make()->success()->title("Contrato cancelado. OT canceladas: {$r['canceladas']}")->send();
                }),
            Action::make('recalcular')
                ->label('Recalcular calendario')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn () => $this->record->estado === EstadoContrato::Activo)
                ->action(function () {
                    $r = app(GeneradorCalendario::class)->recalcular($this->record->fresh());
                    Notification::make()->success()->title("Canceladas: {$r['canceladas']} · Creadas: {$r['creadas']}")->send();
                }),
            Action::make('vistaPrevia')
                ->label('Vista previa PDF')
                ->icon('heroicon-o-document-text')
                ->action(fn () => response()->streamDownload(
                    function () {
                        echo app(\App\Services\ContratoPdf::class)->render($this->record);
                    },
                    'contrato-' . $this->record->id . '-previa.pdf'
                )),
            Action::make('enviarFirma')
                ->label('Enviar a firma')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn () => blank($this->record->firmado_at) && $this->record->estado !== EstadoContrato::Cancelado)
                ->action(function () {
                    $c = $this->record;
                    $faltan = [];
                    if (blank($c->servicios_incluidos)) { $faltan[] = 'servicios incluidos'; }
                    if ((float) $c->cuota_monto <= 0) { $faltan[] = 'cuota'; }
                    if (blank($c->vigencia_inicio) || blank($c->vigencia_fin)) { $faltan[] = 'vigencia'; }
                    if ($c->equipos()->count() === 0) { $faltan[] = 'equipos'; }
                    if ($faltan) {
                        Notification::make()->danger()->title('Faltan datos para enviar a firma')
                            ->body('Complete: ' . implode(', ', $faltan))->send();
                        return;
                    }
                    if (blank($c->token_firma)) {
                        $c->update(['token_firma' => \Illuminate\Support\Str::random(48)]);
                    }
                    $c->update(['firma_enviada_at' => now()]);
                    Notification::make()->success()->persistent()
                        ->title('Enlace de firma del cliente')
                        ->body(route('contratos.firma', $c->token_firma))
                        ->send();
                }),
            Action::make('descargarFirmado')
                ->label('Descargar contrato firmado')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => filled($this->record->ruta_pdf))
                ->action(fn () => \Illuminate\Support\Facades\Storage::disk('local')
                    ->download($this->record->ruta_pdf, 'contrato-' . $this->record->id . '-firmado.pdf')),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        if (filled($this->record->firmado_at)) {
            Notification::make()->danger()->title('Contrato firmado: no se puede editar')->send();
            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        if ($this->record->estado === EstadoContrato::Activo) {
            app(GeneradorCalendario::class)->recalcular($this->record->fresh());
        }
    }
}
