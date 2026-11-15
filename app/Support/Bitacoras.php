<?php

namespace App\Support;

use App\Enums\EstadoOt;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Spatie\Activitylog\Models\Activity;

class Bitacoras
{
    public const DEFINICIONES = [
        'ot' => [
            'roles' => ['administrador', 'coordinador'],
            'tipos' => [
                \App\Models\OrdenTrabajo::class, \App\Models\Informe::class, \App\Models\ActaConformidad::class,
                \App\Models\TrabajoAdicional::class, \App\Models\FichaTecnica::class, \App\Models\PlanPreventivo::class,
                \App\Models\Garantia::class,
            ],
        ],
        'ventas' => [
            'roles' => ['administrador', 'coordinador', 'comercial'],
            'tipos' => [\App\Models\Cotizacion::class, \App\Models\Cliente::class, \App\Models\Sucursal::class],
        ],
        'cobros' => [
            'roles' => ['administrador'],
            'tipos' => [\App\Models\Contrato::class, \App\Models\ContratoEquipo::class, \App\Models\Factura::class, \App\Models\Pago::class],
        ],
        'rrhh' => [
            'roles' => ['administrador', 'rrhh'],
            'tipos' => [
                \App\Models\Colaborador::class, \App\Models\Vinculo::class, \App\Models\DocumentoColaborador::class,
                \App\Models\Tecnico::class, \App\Models\DocumentoTecnico::class,
            ],
        ],
        'inventario' => [
            'roles' => ['administrador', 'coordinador'],
            'tipos' => [
                \App\Models\Insumo::class, \App\Models\Refrigerante::class, \App\Models\MovimientoRefrigerante::class,
                \App\Models\CilindroRefrigerante::class, \App\Models\Proveedor::class, \App\Models\Marca::class,
                \App\Models\TipoEquipo::class, \App\Models\Equipo::class, \App\Models\Area::class,
                \App\Models\PlantillaFormulario::class, \App\Models\PlantillaAjusteCliente::class,
                \App\Models\DocumentoTercero::class, \App\Models\Division::class,
            ],
        ],
    ];

    public static function puedeVer(string $clave): bool
    {
        return auth()->user()?->hasAnyRole(self::DEFINICIONES[$clave]['roles']) ?? false;
    }

    public static function tabla(Table $table, string $clave): Table
    {
        $tipos = self::DEFINICIONES[$clave]['tipos'];

        return $table
            ->modifyQueryUsing(function (Builder $q) use ($tipos) {
                $q->with(['causer', 'subject'])->whereIn('subject_type', $tipos);

                $u = auth()->user();
                if ($u?->hasRole('comercial') && ! $u->hasAnyRole(['administrador', 'coordinador'])) {
                    $q->where('causer_type', $u->getMorphClass())->where('causer_id', $u->getKey());
                }
            })
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('causer')->label('Usuario')
                    ->getStateUsing(fn (Activity $r) => $r->causer?->name ?? 'Sistema'),
                TextColumn::make('event')->label('Evento')->badge()
                    ->color(fn ($state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        'restored' => 'warning',
                        default => 'gray',
                    })->placeholder(''),
                TextColumn::make('subject_type')->label('Módulo')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '—'),
                TextColumn::make('registro')->label('Registro')
                    ->getStateUsing(function (Activity $r) {
                        $a = $r->subject?->getAttributes() ?? [];

                        return $a['numero'] ?? $a['nombre'] ?? $a['cargo'] ?? ('#' . $r->subject_id);
                    }),
                TextColumn::make('description')->label('Descripción')->searchable()->wrap(),
            ])
            ->filters([
                SelectFilter::make('event')->label('Evento')
                    ->options(fn () => Activity::query()->whereIn('subject_type', $tipos)->whereNotNull('event')
                        ->distinct()->orderBy('event')->pluck('event', 'event')->all()),
                SelectFilter::make('subject_type')->label('Módulo')
                    ->options(collect($tipos)->mapWithKeys(fn ($t) => [$t => class_basename($t)])->all()),
                SelectFilter::make('causer_id')->label('Usuario')->searchable()
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id')->all()),
                Filter::make('fecha')
                    ->schema([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['desde'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['hasta'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->recordActions(self::acciones($clave));
    }

    private static function acciones(string $clave): array
    {
        $acciones = [
            Action::make('detalle')->label('Detalle')->icon('heroicon-o-eye')
                ->modalHeading('Detalle del registro')
                ->modalContent(fn (Activity $record) => view('filament.bitacora.detalle', ['record' => $record]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar'),
        ];

        if ($clave === 'ot') {
            $acciones[] = Action::make('linea')->label('Línea de tiempo')->icon('heroicon-o-clock')
                ->visible(fn (Activity $r) => $r->subject_type === \App\Models\OrdenTrabajo::class)
                ->modalHeading(fn (Activity $r) => 'Línea de tiempo · OT ' . ($r->subject?->numero ?? '#' . $r->subject_id))
                ->modalContent(fn (Activity $r) => self::lineaOt((int) $r->subject_id))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar');
        }

        return $acciones;
    }

    private static function lineaOt(int $id): HtmlString
    {
        $filas = DB::table('historial_estados_ot as h')
            ->leftJoin('users as u', 'u.id', '=', 'h.usuario_id')
            ->where('h.orden_trabajo_id', $id)
            ->orderBy('h.created_at')->orderBy('h.id')
            ->get(['h.created_at', 'h.estado_anterior', 'h.estado_nuevo', 'h.motivo', 'u.name']);

        if ($filas->isEmpty()) {
            return new HtmlString('<p>Sin cambios de estado registrados.</p>');
        }

        $items = $filas->map(fn ($f) => sprintf(
            '<li style="margin-bottom:.75rem"><strong>%s</strong> · %s<br>%s → <strong>%s</strong>%s</li>',
            e(Carbon::parse($f->created_at)->format('d/m/Y H:i')),
            e($f->name ?? 'Sistema'),
            e(self::estado($f->estado_anterior)),
            e(self::estado($f->estado_nuevo)),
            $f->motivo ? '<br><em>' . e($f->motivo) . '</em>' : '',
        ))->implode('');

        return new HtmlString('<ol style="list-style:decimal;padding-left:1.25rem">' . $items . '</ol>');
    }

    private static function estado(?string $valor): string
    {
        return $valor ? (EstadoOt::tryFrom($valor)?->getLabel() ?? $valor) : 'Nueva';
    }
}
