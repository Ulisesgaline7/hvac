<?php

namespace App\Filament\Resources\MenusWeb\Pages;

use App\Filament\Resources\MenusWeb\MenuItemWebResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageMenusWeb extends ManageRecords
{
    protected static string $resource = MenuItemWebResource::class;

    public function getTabs(): array
    {
        return [
            'principal' => Tab::make('Menú superior')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('ubicacion', 'principal')),
            'pie' => Tab::make('Pie de página')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('ubicacion', 'pie')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->fillForm(fn () => ['ubicacion' => $this->activeTab ?: 'principal']),
        ];
    }
}
