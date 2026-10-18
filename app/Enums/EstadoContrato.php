<?php

namespace App\Enums;

enum EstadoContrato: string
{
    case Borrador = 'borrador';
    case Activo = 'activo';
    case Vencido = 'vencido';
    case Cancelado = 'cancelado';
}
