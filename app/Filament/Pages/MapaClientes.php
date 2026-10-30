<?php

namespace App\Filament\Pages;

use App\Models\OrdenTrabajo;
use App\Models\Sucursal;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class MapaClientes extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Mapa de clientes';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'mapa-clientes';

    protected static ?string $title = 'Mapa de clientes';

    protected string $view = 'filament.pages.mapa-clientes';

    public function puntos(): array
    {
        $abiertas = OrdenTrabajo::query()
            ->whereNull('cerrada_en')
            ->where('estado', 'not like', '%cancel%')
            ->selectRaw('sucursal_id, count(*) as c')
            ->groupBy('sucursal_id')
            ->pluck('c', 'sucursal_id');

        return Sucursal::query()->with('cliente:id,razon_social')
            ->whereNotNull('latitud')->whereNotNull('longitud')
            ->get()
            ->map(fn (Sucursal $s) => [
                'id' => $s->id,
                'cliente' => $s->cliente?->razon_social ?? '',
                'nombre' => $s->nombre,
                'direccion' => $s->direccion,
                'lat' => (float) $s->latitud,
                'lng' => (float) $s->longitud,
                'abiertas' => (int) ($abiertas[$s->id] ?? 0),
                'url' => route('filament.admin.resources.sucursales.edit', ['record' => $s->id]),
            ])->values()->all();
    }

    public function sinUbicacion(): array
    {
        return Sucursal::query()->with('cliente:id,razon_social')
            ->where(fn ($q) => $q->whereNull('latitud')->orWhereNull('longitud'))
            ->get()
            ->map(fn (Sucursal $s) => [
                'cliente' => $s->cliente?->razon_social ?? '',
                'nombre' => $s->nombre,
                'url' => route('filament.admin.resources.sucursales.edit', ['record' => $s->id]),
            ])->all();
    }
}
