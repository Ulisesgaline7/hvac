<?php

namespace App\Filament\Widgets;

use App\Models\CilindroRefrigerante;
use App\Models\Tecnico;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumenAdminWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Administración';

    private const SALDO_BAJO_KG = 2;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('administrador') ?? false;
    }

    protected function getStats(): array
    {
        $bajos = CilindroRefrigerante::where('estado', 'activo')->get()
            ->filter(fn ($c) => $c->saldoKg() < self::SALDO_BAJO_KG)->count();

        return [
            Stat::make('Técnicos por aprobar', Tecnico::where('estado_homologacion', 'pendiente')->count())->color('warning'),
            Stat::make('Técnicos aprobados', Tecnico::where('estado_homologacion', 'aprobado')->count())->color('success'),
            Stat::make('Cilindros con saldo bajo', $bajos)
                ->description('Menos de ' . self::SALDO_BAJO_KG . ' kg')->color($bajos ? 'danger' : 'success'),
        ];
    }
}
