<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Cotizacions\CotizacionResource;
use App\Models\Area;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Equipo;
use App\Models\ServicioCatalogo;
use App\Models\Sucursal;
use App\Services\CotizacionService;
use App\Services\ResumenAnual;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class CotizadorRapido extends Page implements HasSchemas
{
    protected static string|\UnitEnum|null $navigationGroup = 'Comercial';

    protected static ?int $navigationSort = 1;

    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $navigationLabel = 'Cotizador rápido';

    protected static ?string $title = 'Cotizador rápido';

    protected static ?string $slug = 'cotizador-rapido';

    protected string $view = 'filament.pages.cotizador-rapido';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'fecha' => now()->toDateString(),
            'valida_hasta' => now()->addWeekdays((int) config('tarifario.validez_dias_habiles'))->toDateString(),
            'equipos' => [],
            'servicios' => [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Wizard::make([
                Step::make('Cliente y equipos')->schema([
                    Select::make('cliente_id')->label('Cliente')->required()->searchable()->live()
                        ->createOptionForm([TextInput::make('razon_social')->label('Nombre o razón social')->required(), TextInput::make('identificacion_fiscal')->label('RTN o identidad (opcional)')])
                        ->createOptionUsing(fn (array $data) => Cliente::forceCreate(['razon_social' => $data['razon_social'], 'identificacion_fiscal' => $data['identificacion_fiscal'] ?: null, 'creado_por' => auth()->id()])->id)
                        ->options(fn () => Cliente::orderBy('razon_social')->pluck('razon_social', 'id')->all())
                        ->afterStateUpdated(function ($state, Set $set) {
                            $ids = $state ? Sucursal::where('cliente_id', $state)->pluck('id') : collect();
                            $set('sucursal_id', $ids->count() === 1 ? $ids->first() : null);
                            $set('equipos', []);
                        }),
                    Select::make('sucursal_id')->label('Sucursal')->required()->live()
                        ->createOptionForm([TextInput::make('nombre')->required(), TextInput::make('direccion')->label('Dirección')])
                        ->createOptionUsing(fn (array $data, Get $get) => Sucursal::forceCreate(['cliente_id' => $get('cliente_id'), 'nombre' => $data['nombre'], 'direccion' => $data['direccion'] ?? null, 'creado_por' => auth()->id()])->id)
                        ->options(fn (Get $get) => $get('cliente_id')
                            ? Sucursal::where('cliente_id', $get('cliente_id'))->orderBy('nombre')->pluck('nombre', 'id')->all()
                            : [])
                        ->afterStateUpdated(fn (Set $set) => $set('equipos', [])),
                    CheckboxList::make('equipos')->label('Equipos a atender (opcional)')->columns(2)->bulkToggleable()
                        ->options(function (Get $get) {
                            if (! $get('sucursal_id')) {
                                return [];
                            }

                            return Equipo::with('tipo')
                                ->whereIn('area_id', Area::where('sucursal_id', $get('sucursal_id'))->select('id'))
                                ->orderBy('codigo')->get()
                                ->mapWithKeys(fn ($e) => [$e->id => trim($e->codigo . ' · ' . ($e->tipo?->nombre ?? '') . ' ' . ($e->modelo ?? ''))])
                                ->all();
                        })
                        ->helperText('Déjelo vacío si el cliente es nuevo: los equipos se registran en la primera visita.'),
                ]),

                Step::make('Servicios')->schema([
                    Repeater::make('servicios')->label('Servicios a cotizar')->minItems(1)->addActionLabel('Agregar servicio')->columns(3)
                        ->schema([
                            Select::make('servicio_id')->label('Servicio del tarifario')->required()->searchable()->live()->columnSpanFull()
                                ->options(fn () => ServicioCatalogo::where('activo', true)->orderBy('codigo')->get()
                                    ->mapWithKeys(fn ($s) => [$s->id => "{$s->codigo} · {$s->nombre}"])->all())
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $s = $state ? ServicioCatalogo::find($state) : null;
                                    if (! $s) {
                                        return;
                                    }
                                    $set('descripcion', $s->nombre . ($s->detalle ? ' – ' . $s->detalle : ''));
                                    $set('precio_unitario', $s->precio);
                                    $n = count($get('../../equipos') ?? []);
                                    $set('cantidad', $s->cuenta_equipo && $n > 0 ? $n : 1);
                                }),
                            TextInput::make('descripcion')->label('Descripción')->required()->columnSpanFull(),
                            TextInput::make('cantidad')->numeric()->minValue(0.01)->default(1)->required(),
                            TextInput::make('precio_unitario')->label('Precio unitario (L, sin ISV)')->numeric()->minValue(0.01)->required(),
                        ]),
                ]),

                Step::make('Condiciones')->schema([
                    DatePicker::make('fecha')->required(),
                    DatePicker::make('valida_hasta')->label('Válida hasta'),
                    CheckboxList::make('recargos')->label('Recargos por condiciones especiales')->columns(2)
                        ->options(collect(config('tarifario.recargos'))->map(fn ($r) => $r['nombre'])->all()),
                    Select::make('descuento_contrato')->label('Descuento por contrato anual')->placeholder('Sin contrato')
                        ->options(collect(config('tarifario.descuentos'))->filter(fn ($d) => ! isset($d['desde']))->map(fn ($d) => $d['nombre'])->all()),
                ]),
            ])->submitAction(new HtmlString(Blade::render(
                '<x-filament::button type="submit">Crear cotización</x-filament::button>'
            ))),
        ]);
    }

    public function crear(): void
    {
        $d = $this->form->getState();

        $c = DB::transaction(function () use ($d) {
            $c = Cotizacion::create([
                'cliente_id' => $d['cliente_id'],
                'sucursal_id' => $d['sucursal_id'],
                'vendedor_id' => auth()->id(),
                'fecha' => $d['fecha'],
                'valida_hasta' => $d['valida_hasta'] ?? null,
                'recargos' => $d['recargos'] ?? [],
                'descuento_contrato' => $d['descuento_contrato'] ?? null,
            ]);

            $c->equipos()->sync($d['equipos'] ?? []);

            foreach ($d['servicios'] as $s) {
                $cat = ServicioCatalogo::find($s['servicio_id']);
                $c->lineas()->create([
                    'tipo' => 'servicio',
                    'tipo' => 'servicio',
                    'servicio_id' => $cat->id,
                    'codigo' => $cat->codigo,
                    'descripcion' => $s['descripcion'],
                    'cantidad' => $s['cantidad'],
                    'precio_unitario' => $s['precio_unitario'],
                    'cuenta_equipo' => (bool) $cat->cuenta_equipo,
                ]);
            }

            $c = app(CotizacionService::class)->recalcular($c->fresh());
            $c->update(['condiciones' => ResumenAnual::condiciones($c->fresh())]);

            return $c;
        });

        Notification::make()->title('Cotización ' . $c->numero . ' creada en borrador')->success()->send();

        $this->redirect(CotizacionResource::getUrl('edit', ['record' => $c]));
    }
}
