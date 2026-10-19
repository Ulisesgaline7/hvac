<?php

namespace App\Filament\Resources\Facturas\Tables;

use App\Models\Factura;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FacturasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fecha', 'desc')
            ->columns([
                TextColumn::make('numero')->label('Factura')->searchable()->sortable(),
                TextColumn::make('cliente.razon_social')->label('Cliente')->searchable(),
                TextColumn::make('origen')->label('Origen')
                    ->state(fn (Factura $r) => $r->orden_trabajo_id
                        ? 'OT ' . ($r->ordenTrabajo?->numero ?? $r->orden_trabajo_id)
                        : ($r->contrato_id ? 'Contrato #' . $r->contrato_id . ' · ' . $r->periodo?->format('m/Y') : '-')),
                TextColumn::make('fecha')->date('d/m/Y')->sortable(),
                TextColumn::make('vence')->date('d/m/Y')->sortable()
                    ->color(fn (Factura $r) => $r->vencida ? 'danger' : null),
                TextColumn::make('total')->money('HNL')->sortable(),
                TextColumn::make('pagado')->money('HNL'),
                TextColumn::make('saldo')->label('Saldo')->money('HNL')
                    ->state(fn (Factura $r) => $r->saldo),
                TextColumn::make('estado')->badge()->color(fn (string $state) => match ($state) {
                    'pagada' => 'success',
                    'parcial' => 'warning',
                    'anulada' => 'gray',
                    default => 'info',
                }),
            ])
            ->filters([
                SelectFilter::make('estado')->options([
                    'emitida' => 'Emitida', 'parcial' => 'Parcial', 'pagada' => 'Pagada', 'anulada' => 'Anulada',
                ]),
                Filter::make('por_cobrar')->label('Con saldo pendiente')
                    ->query(fn ($q) => $q->whereIn('estado', ['emitida', 'parcial'])),
                Filter::make('vencidas')->label('Vencidas')
                    ->query(fn ($q) => $q->whereIn('estado', ['emitida', 'parcial'])->whereDate('vence', '<', now())),
            ])
            ->recordActions([
                Action::make('ver')->label('Ver')->url(fn (Factura $r) => \App\Filament\Resources\Facturas\FacturaResource::getUrl('view', ['record' => $r])),
                self::accionRegistrarPago(),
            ]);
    }

    public static function accionRegistrarPago(): Action
    {
        return Action::make('registrarPago')
            ->label('Registrar pago')
            ->icon('heroicon-o-banknotes')
            ->visible(fn (Factura $r) => in_array($r->estado, ['emitida', 'parcial'], true))
            ->schema(fn (Factura $r) => [
                TextInput::make('monto')->label('Monto (L)')->numeric()->required()
                    ->minValue(0.01)->maxValue($r->saldo)->default($r->saldo)
                    ->helperText('Saldo pendiente: L ' . number_format($r->saldo, 2)),
                DatePicker::make('fecha')->default(now())->required(),
                Select::make('metodo')->label('Método')->options([
                    'efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'cheque' => 'Cheque', 'tarjeta' => 'Tarjeta',
                ])->required(),
                TextInput::make('referencia')->label('Referencia / comprobante'),
            ])
            ->action(function (Factura $r, array $data) {
                if ((float) $data['monto'] > $r->saldo + 0.001) {
                    Notification::make()->title('El monto excede el saldo')->danger()->send();

                    return;
                }

                $r->pagos()->create($data + ['cliente_id' => $r->cliente_id, 'tipo' => 'pago']);

                Notification::make()->title('Pago registrado')->success()->send();
            });
    }
}
