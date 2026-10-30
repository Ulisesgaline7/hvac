<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $fillable = ['insumo_id', 'tipo', 'cantidad', 'costo_unitario', 'stock_resultante', 'orden_trabajo_id', 'user_id', 'motivo'];

    protected $casts = ['cantidad' => 'decimal:2', 'costo_unitario' => 'decimal:2', 'stock_resultante' => 'decimal:2'];

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class)->withTrashed();
    }

    public function ordenTrabajo(): BelongsTo
    {
        return $this->belongsTo(OrdenTrabajo::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
