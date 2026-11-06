<?php

namespace App\Filament\Resources\LiquidacionesTecnico\Pages;

use App\Filament\Resources\LiquidacionesTecnico\LiquidacionTecnicoResource;
use App\Services\LiquidacionService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListLiquidacionesTecnico extends ListRecords
{
    protected static string $resource = LiquidacionTecnicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generarSemana')
                ->label('Generar semana')
                ->icon('heroicon-o-calculator')
                ->color('primary')
                ->modalHeading('Generar liquidaciones de la semana')
                ->modalDescription('Agrupa por técnico los pagos aprobados hasta el fin de la semana elegida. No repite técnicos con liquidación activa en esa semana.')
                ->schema([
                    DatePicker::make('fecha')->label('Cualquier día de la semana')->default(now())->required(),
                ])
                ->action(function (array $data) {
                    $r = app(LiquidacionService::class)->generarSemana((string) $data['fecha']);
                    Notification::make()
                        ->title($r['creadas'] > 0 ? "Liquidaciones creadas: {$r['creadas']}" : 'No había pagos aprobados por liquidar')
                        ->body($r['omitidas'] > 0 ? "Omitidas por ya existir en esa semana: {$r['omitidas']}" : null)
                        ->{$r['creadas'] > 0 ? 'success' : 'warning'}()
                        ->send();
                }),
        ];
    }
}
