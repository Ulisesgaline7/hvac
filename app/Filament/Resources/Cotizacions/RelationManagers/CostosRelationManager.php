<?php

namespace App\Filament\Resources\Cotizacions\RelationManagers;

use App\Models\CotizacionLineaCosto;
use App\Models\Insumo;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CostosRelationManager extends RelationManager
{
    protected static string $relationship = 'lineas';

    protected static ?string $title = 'Costos y margen';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->hasAnyRole(['administrador', 'coordinador']) ?? false;
    }

    private function margen($r): array
    {
        $costo = (float) ($r->costo?->costo_unitario ?? 0);
        $precio = (float) $r->precio_unitario;
        $monto = round(($precio - $costo) * (float) $r->cantidad, 2);
        $pct = $precio > 0 ? round(($precio - $costo) / $precio * 100, 1) : 0;

        return [$costo, $monto, $pct];
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($q) => $q->with('costo'))
            ->description(function () {
                $tot = 0.0;
                foreach ($this->getOwnerRecord()->lineas()->with('costo')->get() as $l) {
                    $tot += $this->margen($l)[1];
                }

                return 'Margen total estimado (sin ISV): L ' . number_format($tot, 2);
            })
            ->columns([
                TextColumn::make('descripcion')->limit(40),
                TextColumn::make('tipo')->badge(),
                TextColumn::make('cantidad'),
                TextColumn::make('precio_unitario')->label('Precio')
                    ->formatStateUsing(fn ($state) => 'L ' . number_format((float) $state, 2)),
                TextColumn::make('costo_u')->label('Costo')
                    ->getStateUsing(fn ($r) => 'L ' . number_format($this->margen($r)[0], 2)),
                TextColumn::make('margen_l')->label('Margen')
                    ->getStateUsing(fn ($r) => 'L ' . number_format($this->margen($r)[1], 2)),
                TextColumn::make('margen_p')->label('Margen %')
                    ->getStateUsing(fn ($r) => $this->margen($r)[2] . ' %'),
            ])
            ->recordActions([
                Action::make('costo')->label('Costo')->icon('heroicon-o-currency-dollar')
                    ->fillForm(fn ($record) => [
                        'insumo_id' => $record->costo?->insumo_id,
                        'costo_unitario' => $record->costo?->costo_unitario ?? 0,
                    ])
                    ->schema([
                        Select::make('insumo_id')->label('Insumo del inventario (opcional)')
                            ->searchable()->live()
                            ->options(fn () => Insumo::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')->all())
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state && ($i = Insumo::find($state))) {
                                    $set('costo_unitario', $i->costo);
                                }
                            }),
                        TextInput::make('costo_unitario')->label('Costo unitario (L)')
                            ->numeric()->minValue(0)->required(),
                    ])
                    ->action(function (array $data, $record) {
                        CotizacionLineaCosto::updateOrCreate(
                            ['cotizacion_linea_id' => $record->id],
                            ['insumo_id' => $data['insumo_id'] ?? null, 'costo_unitario' => $data['costo_unitario']],
                        );
                    }),
            ]);
    }
}
