<?php

namespace App\Filament\Resources\OrdenTrabajos\Pages;

use App\Enums\EstadoOt;
use App\Models\OrdenTrabajo;
use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Resources\OrdenTrabajos\OrdenTrabajoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrdenTrabajos extends ListRecords
{
    protected static string $resource = OrdenTrabajoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'abiertas' => Tab::make('Abiertas')
                ->modifyQueryUsing(fn ($query) => $query->abiertas())
                ->badge(fn () => OrdenTrabajo::abiertas()->count()),
            'sin_asignar' => Tab::make('Sin asignar')
                ->modifyQueryUsing(fn ($query) => $query->sinAsignar())
                ->badge(fn () => OrdenTrabajo::sinAsignar()->count())
                ->badgeColor('warning'),
            'atrasadas' => Tab::make('Atrasadas')
                ->modifyQueryUsing(fn ($query) => $query->atrasadas())
                ->badge(fn () => OrdenTrabajo::atrasadas()->count())
                ->badgeColor('danger'),
            'revision' => Tab::make('En revisión')
                ->modifyQueryUsing(fn ($query) => $query->where('estado', EstadoOt::EnRevision->value))
                ->badge(fn () => OrdenTrabajo::where('estado', EstadoOt::EnRevision->value)->count())
                ->badgeColor('info'),
            'cerradas' => Tab::make('Cerradas')
                ->modifyQueryUsing(fn ($query) => $query->where('estado', EstadoOt::Cerrada->value)),
            'todas' => Tab::make('Todas'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'abiertas';
    }
}
