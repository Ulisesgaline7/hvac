<?php

namespace App\Filament\Pages;

use App\Services\FinanzasResumen;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Finanzas extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Finanzas';

    protected static ?string $title = 'Finanzas';

    protected static ?string $slug = 'finanzas';

    protected string $view = 'filament.pages.finanzas';

    public ?string $desde = null;

    public ?string $hasta = null;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrador', 'coordinador']);
    }

    public function mount(): void
    {
        $this->desde = now()->startOfMonth()->toDateString();
        $this->hasta = now()->toDateString();
        $this->g = $this->gVacio();
    }

    public array $g = [];

    private function gVacio(): array
    {
        return ['fecha' => now()->toDateString(), 'proveedor' => '', 'concepto' => '', 'categoria' => 'insumos',
            'subtotal' => null, 'isv' => null, 'factura' => '', 'metodo' => '', 'proveedor_id' => ''];
    }

    public function guardarGasto(): void
    {
        abort_unless(static::canAccess(), 403);

        $pid = (int) ($this->g['proveedor_id'] ?? 0);
        if ($pid) {
            $nombre = \Illuminate\Support\Facades\DB::table('proveedores')->where('id', $pid)->value('nombre');
            if ($nombre) {
                $this->g['proveedor'] = $nombre;
            } else {
                $pid = 0;
            }
        }

        $this->validate([
            'g.fecha' => 'required|date',
            'g.proveedor' => 'required|string|max:150',
            'g.concepto' => 'required|string|max:255',
            'g.categoria' => 'required|in:' . implode(',', array_keys(\App\Services\FinanzasGastos::CATEGORIAS)),
            'g.subtotal' => 'required|numeric|min:0.01',
            'g.isv' => 'nullable|numeric|min:0',
            'g.factura' => 'nullable|string|max:60',
            'g.metodo' => 'nullable|string|max:30',
        ], [], ['g.fecha' => 'fecha', 'g.proveedor' => 'proveedor', 'g.concepto' => 'concepto', 'g.subtotal' => 'monto', 'g.isv' => 'ISV']);

        $sub = round((float) $this->g['subtotal'], 2);
        $isv = round((float) ($this->g['isv'] ?: 0), 2);

        \Illuminate\Support\Facades\DB::table('gastos')->insert([
            'fecha' => $this->g['fecha'],
            'proveedor' => trim($this->g['proveedor']),
            'proveedor_id' => $pid ?: null,
            'concepto' => trim($this->g['concepto']),
            'categoria' => $this->g['categoria'],
            'factura_proveedor' => $this->g['factura'] ?: null,
            'subtotal' => $sub,
            'isv' => $isv,
            'total' => round($sub + $isv, 2),
            'metodo' => $this->g['metodo'] ?: null,
            'creado_por' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->g = $this->gVacio();
        \Filament\Notifications\Notification::make()->success()->title('Gasto registrado')->send();
    }

    public function borrarGasto(int $id): void
    {
        abort_unless(static::canAccess(), 403);
        \Illuminate\Support\Facades\DB::table('gastos')->where('id', $id)->delete();
        \Filament\Notifications\Notification::make()->success()->title('Gasto borrado')->send();
    }

    public function extra(): array
    {
        [$desde, $hasta] = $this->rango();
        $x = app(\App\Services\FinanzasGastos::class)->datos($desde, $hasta);
        $base = app(FinanzasResumen::class)->datos($desde, $hasta);
        $x['facturado_sin_isv'] = $base['facturado']['subtotal'];
        $x['utilidad'] = round($base['facturado']['subtotal'] - $x['gastos']['total']['subtotal'] - $x['tecnicos']['total_pagos'], 2);
        $x['categorias'] = \App\Services\FinanzasGastos::CATEGORIAS;
        $x['inv'] = app(\App\Services\FinanzasInventario::class)->datos($desde, $hasta);
        $x['proveedores'] = \Illuminate\Support\Facades\DB::table('proveedores')->where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')->all();

        return $x;
    }

    private function rango(): array
    {
        try {
            $desde = Carbon::parse($this->desde ?: now()->startOfMonth());
            $hasta = Carbon::parse($this->hasta ?: now());
            if ($hasta->lt($desde)) {
                [$desde, $hasta] = [$hasta, $desde];
            }
        } catch (\Throwable) {
            $desde = now()->startOfMonth();
            $hasta = now();
        }

        return [$desde, $hasta];
    }

    public function datos(): array
    {
        try {
            $desde = Carbon::parse($this->desde ?: now()->startOfMonth());
            $hasta = Carbon::parse($this->hasta ?: now());
            if ($hasta->lt($desde)) {
                [$desde, $hasta] = [$hasta, $desde];
            }
        } catch (\Throwable) {
            $desde = now()->startOfMonth();
            $hasta = now();
        }

        return app(FinanzasResumen::class)->datos($desde, $hasta);
    }
}
