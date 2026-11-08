<?php

namespace App\Support;

use App\Enums\EstadoOt;
use App\Enums\TipoOt;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

/** Botones de descarga de documentos de entrega, compartidos por el panel de OT y MisOrdenes. */
class AccionesEntregables
{
    /** @param Closure():\App\Models\OrdenTrabajo $ot */
    public static function hacer(Closure $ot): array
    {
        $terminada = fn () => in_array($ot()->estado, [EstadoOt::CompletadaTecnico, EstadoOt::EnRevision, EstadoOt::Cerrada], true);
        $para = fn (array $tipos) => fn () => $terminada() && in_array($ot()->tipo, $tipos, true);

        $garantia = $para([TipoOt::Instalacion, TipoOt::Emergencia, TipoOt::Correctivo]);
        $capacitacion = $para([TipoOt::Instalacion]);
        $calendario = $para([TipoOt::Instalacion, TipoOt::Correctivo, TipoOt::Preventivo]);

        return [
            Action::make('entregable_garantia')->label('Garantía (PDF)')->icon('heroicon-o-shield-check')->color('gray')
                ->visible($garantia)
                ->url(fn () => route('ordenes-trabajo.entregable', [$ot()->id, 'garantia']), shouldOpenInNewTab: true),
            Action::make('entregable_capacitacion')->label('Capacitación (PDF)')->icon('heroicon-o-academic-cap')->color('gray')
                ->visible($capacitacion)
                ->url(fn () => route('ordenes-trabajo.entregable', [$ot()->id, 'capacitacion']), shouldOpenInNewTab: true),
            Action::make('entregable_calendario')->label('Calendario de mantenimiento')->icon('heroicon-o-calendar-days')->color('gray')
                ->visible($calendario)
                ->modalHeading('Propuesta de calendario de mantenimiento')
                ->modalDescription('Se calcula desde la fecha de cierre de la OT. No sustituye a un contrato preventivo.')
                ->modalSubmitActionLabel('Generar PDF')
                ->schema([
                    Select::make('cada')->label('Frecuencia')->required()->default(3)
                        ->options([1 => 'Mensual', 2 => 'Bimestral', 3 => 'Trimestral', 4 => 'Cuatrimestral', 6 => 'Semestral', 12 => 'Anual']),
                    TextInput::make('visitas')->label('Número de visitas')->numeric()->minValue(1)->maxValue(12)->default(4)->required(),
                ])
                ->action(function (array $data, $livewire) use ($ot) {
                    $url = route('ordenes-trabajo.entregable', [$ot()->id, 'calendario']) . '?' . http_build_query(['cada' => $data['cada'], 'visitas' => $data['visitas']]);
                    $livewire->js('window.open(' . json_encode($url) . ', "_blank")');
                }),
        ];
    }
}
