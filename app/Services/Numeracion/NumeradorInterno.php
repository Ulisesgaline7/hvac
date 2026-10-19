<?php

namespace App\Services\Numeracion;

use App\Models\Factura;

/** Numeración interna de prueba (sin valor fiscal). Se reemplaza por una basada en CAI cuando exista autorización del SAR. */
class NumeradorInterno implements NumeradorFactura
{
    public function asignar(Factura $factura): void
    {
        $factura->numero = sprintf('FAC-%s-%06d', now()->format('Y'), $factura->id);
    }
}
