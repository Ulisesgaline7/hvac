<?php

namespace App\Filament\Portal\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CalendarioCliente extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Calendario';

    protected static ?string $title = 'Calendario de servicios';

    protected static ?string $slug = 'calendario';

    protected string $view = 'filament.portal.pages.calendario-cliente';

    public string $mes = '';

    public function mount(): void
    {
        $this->mes = now()->format('Y-m');
    }

    private function inicio(): Carbon
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->mes)) {
            $this->mes = now()->format('Y-m');
        }

        return Carbon::createFromFormat('Y-m-d', $this->mes . '-01')->startOfDay();
    }

    public function anterior(): void
    {
        $this->mes = $this->inicio()->subMonth()->format('Y-m');
    }

    public function siguiente(): void
    {
        $this->mes = $this->inicio()->addMonth()->format('Y-m');
    }

    public function hoy(): void
    {
        $this->mes = now()->format('Y-m');
    }

    public function titulo(): string
    {
        return ucfirst($this->inicio()->locale('es')->translatedFormat('F Y'));
    }

    /** @return array<string, array<int, array{0:string,1:string}>> fecha => [[texto, color]] */
    private function eventos(Carbon $ini, Carbon $fin): array
    {
        $c = auth()->user()?->cliente_id;
        if (! $c) {
            return [];
        }

        $a = $ini->toDateString();
        $b = $fin->toDateString();
        $ev = [];

        foreach (DB::table('ordenes_trabajo')->where('cliente_id', $c)->whereRaw('fecha_programada::date between ? and ?', [$a, $b])
            ->get(['numero', 'tipo', 'estado', 'fecha_programada']) as $r) {
            $color = $r->estado === 'cerrada' ? '#16a34a' : '#2563eb';
            $ev[substr((string) $r->fecha_programada, 0, 10)][] = ['Visita ' . $r->numero . ' · ' . $r->tipo, $color];
        }

        foreach (DB::table('garantias')->join('ordenes_trabajo', 'ordenes_trabajo.id', '=', 'garantias.orden_trabajo_id')
            ->where('ordenes_trabajo.cliente_id', $c)->whereBetween('garantias.hasta', [$a, $b])
            ->get(['garantias.numero', 'garantias.hasta']) as $r) {
            $ev[substr((string) $r->hasta, 0, 10)][] = ['Vence garantía ' . $r->numero, '#d97706'];
        }

        foreach (DB::table('equipos')->join('areas', 'areas.id', '=', 'equipos.area_id')
            ->join('sucursales', 'sucursales.id', '=', 'areas.sucursal_id')
            ->where('sucursales.cliente_id', $c)->whereNull('equipos.deleted_at')
            ->whereBetween('equipos.garantia_hasta', [$a, $b])
            ->get(['equipos.codigo', 'equipos.garantia_hasta']) as $r) {
            $ev[substr((string) $r->garantia_hasta, 0, 10)][] = ['Vence garantía de fábrica ' . $r->codigo, '#dc2626'];
        }

        return $ev;
    }

    public function semanas(): array
    {
        $ini = $this->inicio();
        $fin = $ini->copy()->endOfMonth();
        $ev = $this->eventos($ini, $fin);

        $celdas = array_fill(0, $ini->dayOfWeekIso - 1, null);
        for ($d = $ini->copy(); $d <= $fin; $d->addDay()) {
            $celdas[] = ['dia' => $d->day, 'hoy' => $d->isToday(), 'ev' => $ev[$d->toDateString()] ?? []];
        }
        while (count($celdas) % 7) {
            $celdas[] = null;
        }

        return array_chunk($celdas, 7);
    }

    public function proximas(): array
    {
        $c = auth()->user()?->cliente_id;
        if (! $c) {
            return [];
        }

        return DB::table('ordenes_trabajo')->where('cliente_id', $c)->whereNull('cerrada_en')
            ->whereRaw('fecha_programada::date >= ?', [now()->toDateString()])
            ->orderBy('fecha_programada')->limit(8)
            ->get(['numero', 'tipo', 'fecha_programada'])
            ->map(fn ($r) => [Carbon::parse($r->fecha_programada)->format('d/m/Y'), $r->numero, (string) $r->tipo])
            ->all();
    }
}
