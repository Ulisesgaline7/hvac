<?php

namespace App\Filament\Resources\Equipos\Pages;

use App\Services\ImportadorEquipos;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\Action;
use App\Filament\Resources\Equipos\EquipoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEquipos extends ListRecords
{
    protected static string $resource = EquipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importar')
                ->label('Importar')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalHeading('Importar equipos desde CSV o Excel')
                ->modalDescription('Si una sola fila tiene errores no se importa nada. Descargue la plantilla desde el botón "Plantilla".')
                ->schema([
                    FileUpload::make('archivo')
                        ->label('Archivo (.csv o .xlsx)')
                        ->disk('local')
                        ->directory('importaciones')
                        ->visibility('private')
                        ->acceptedFileTypes([
                            'text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $ruta = Storage::disk('local')->path($data['archivo']);
                    $resultado = app(ImportadorEquipos::class)->importar($ruta);
                    Storage::disk('local')->delete($data['archivo']);

                    if ($resultado['errores']) {
                        $lineas = collect($resultado['errores'])->take(15)
                            ->map(fn ($e) => "Fila {$e['fila']}: {$e['mensaje']}")->implode("\n");
                        $mas = count($resultado['errores']) - 15;
                        Notification::make()->danger()->persistent()
                            ->title('No se importó nada: ' . count($resultado['errores']) . ' error(es)')
                            ->body(nl2br(e($lineas . ($mas > 0 ? "\n... y $mas más." : ''))))
                            ->send();
                        return;
                    }

                    Notification::make()->success()
                        ->title($resultado['creados'] . ' equipos importados')
                        ->send();
                }),
            Action::make('plantilla')
                ->label('Plantilla')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(route('equipos.plantilla')),
            CreateAction::make(),
        ];
    }
}
