<?php

namespace App\Services\Numeracion;

use App\Models\Factura;

interface NumeradorFactura
{
    /** Asigna el número (y los datos fiscales que correspondan) a una factura recién creada. No guarda: lo hace quien llama. */
    public function asignar(Factura $factura): void;
}
