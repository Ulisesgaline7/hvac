<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Insumo extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'insumos';

    protected $fillable = ['codigo', 'nombre', 'categoria', 'unidad', 'costo', 'precio_venta', 'stock', 'stock_minimo', 'ubicacion', 'notas', 'activo'];

    protected $casts = [
        'costo' => 'decimal:2', 'precio_venta' => 'decimal:2', 'stock' => 'decimal:2',
        'stock_minimo' => 'decimal:2', 'activo' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['codigo', 'nombre', 'categoria', 'unidad', 'costo', 'precio_venta', 'stock_minimo', 'ubicacion', 'activo'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    /** tipo: entrada | salida | ajuste (en ajuste, $cantidad es lo contado). */
    public function registrarMovimiento(string $tipo, float $cantidad, ?int $otId = null, ?float $costo = null, ?string $motivo = null): MovimientoInventario
    {
        return DB::transaction(function () use ($tipo, $cantidad, $otId, $costo, $motivo) {
            $i = static::query()->lockForUpdate()->findOrFail($this->id);
            $actual = (float) $i->stock;
            $delta = match ($tipo) {
                'entrada' => abs($cantidad),
                'salida' => -abs($cantidad),
                default => $cantidad - $actual,
            };
            if ($actual + $delta < 0) {
                throw new DomainException('No hay suficiente stock. Disponible: ' . number_format($actual, 2) . ' ' . $i->unidad . '.');
            }
            $nuevo = round($actual + $delta, 2);
            $i->stock = $nuevo;
            if ($tipo === 'entrada' && $costo !== null) {
                $i->costo = $costo;
            }
            $i->save();

            $mov = $i->movimientos()->create([
                'tipo' => $tipo, 'cantidad' => $delta, 'costo_unitario' => $costo ?? $i->costo,
                'stock_resultante' => $nuevo, 'orden_trabajo_id' => $otId,
                'user_id' => auth()->id(), 'motivo' => $motivo,
            ]);

            activity()->performedOn($i)->causedBy(auth()->user())->event('movimiento')
                ->withProperties(['tipo' => $tipo, 'cantidad' => $delta, 'stock_resultante' => $nuevo, 'orden_trabajo_id' => $otId])
                ->log("Movimiento de inventario ({$tipo}): {$i->nombre}");

            return $mov;
        });
    }
}
