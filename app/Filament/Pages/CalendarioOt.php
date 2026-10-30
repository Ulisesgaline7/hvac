<?php

namespace App\Filament\Pages;

use App\Enums\EstadoOt;
use App\Models\OrdenTrabajo;
use App\Models\Tecnico;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class CalendarioOt extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Calendario';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'calendario-ot';

    protected static ?string $title = 'Calendario de órdenes';

    protected string $view = 'filament.pages.calendario-ot';

    public ?string $tecnico = null;

    public ?string $estado = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('nueva')->label('Nueva orden')->icon(Heroicon::OutlinedPlus)
                ->url(route('filament.admin.resources.ordenes-trabajo.create')),
        ];
    }

    public function tecnicos(): array
    {
        return Tecnico::query()->get()
            ->mapWithKeys(fn (Tecnico $t) => [$t->id => $this->nombreTecnico($t)])
            ->sort()->all();
    }

    public function estados(): array
    {
        return collect(EstadoOt::cases())->mapWithKeys(fn ($e) => [$e->value => $this->etiqueta($e)])->all();
    }

    public function leyenda(): array
    {
        return collect(EstadoOt::cases())
            ->map(fn ($e) => ['label' => $this->etiqueta($e), 'color' => $this->color($e->value)])->all();
    }

    public function eventos(string $start, string $end): array
    {
        return OrdenTrabajo::query()
            ->with(['cliente:id,razon_social', 'sucursal:id,nombre', 'tecnico'])
            ->whereNotNull('fecha_programada')
            ->whereBetween('fecha_programada', [substr($start, 0, 10), substr($end, 0, 10)])
            ->when($this->tecnico, fn ($q) => $q->where('tecnico_id', $this->tecnico))
            ->when($this->estado, fn ($q) => $q->where('estado', $this->estado))
            ->get()
            ->map(function (OrdenTrabajo $ot) {
                $estado = $ot->estado instanceof BackedEnum ? $ot->estado->value : (string) $ot->estado;
                $fecha = $ot->fecha_programada->format('Y-m-d');
                $hora = $ot->hora_programada ? substr((string) $ot->hora_programada, 0, 8) : null;
                $color = $this->color($estado);

                return [
                    'id' => $ot->id,
                    'title' => trim(($ot->numero ?: 'OT') . ' · ' . ($ot->cliente?->razon_social ?? '')),
                    'start' => $hora ? "{$fecha}T{$hora}" : $fecha,
                    'allDay' => $hora === null,
                    'url' => route('filament.admin.resources.ordenes-trabajo.edit', ['record' => $ot->id]),
                    'backgroundColor' => $color,
                    'borderColor' => $ot->tardia ? '#DC2626' : $color,
                    'extendedProps' => [
                        'estado' => $this->etiqueta($ot->estado),
                        'sucursal' => $ot->sucursal?->nombre,
                        'tecnico' => $ot->tecnico ? $this->nombreTecnico($ot->tecnico) : null,
                    ],
                ];
            })->values()->all();
    }

    private function nombreTecnico(Tecnico $t): string
    {
        $a = $t->getAttributes();
        foreach (['nombre', 'nombres', 'name', 'nombre_completo'] as $k) {
            if (! empty($a[$k])) {
                return (string) $a[$k];
            }
        }
        if (method_exists($t, 'user') && $t->user?->name) {
            return $t->user->name;
        }

        return 'Técnico #' . $t->id;
    }

    private function etiqueta(mixed $e): string
    {
        if ($e instanceof \Filament\Support\Contracts\HasLabel) {
            return (string) $e->getLabel();
        }
        if (is_object($e) && method_exists($e, 'label')) {
            return (string) $e->label();
        }
        $v = $e instanceof BackedEnum ? $e->value : (string) $e;

        return ucfirst(str_replace('_', ' ', $v));
    }

    private function color(string $v): string
    {
        $v = strtolower($v);

        return match (true) {
            str_contains($v, 'cerr'), str_contains($v, 'complet'), str_contains($v, 'termin'), str_contains($v, 'factur') => '#16A34A',
            str_contains($v, 'cancel'), str_contains($v, 'rechaz') => '#6B7280',
            str_contains($v, 'proceso'), str_contains($v, 'curso'), str_contains($v, 'ejecuc') => '#F59E0B',
            str_contains($v, 'program'), str_contains($v, 'asign') => '#2563EB',
            str_contains($v, 'pend'), str_contains($v, 'nueva'), str_contains($v, 'abiert') => '#EA580C',
            str_contains($v, 'devuel'), str_contains($v, 'reprogram') => '#9333EA',
            default => '#0891B2',
        };
    }
}
