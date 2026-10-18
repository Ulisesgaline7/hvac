<?php

namespace App\Enums;

enum TipoOt: string
{
    case Preventivo = 'preventivo';
    case Correctivo = 'correctivo';
    case Instalacion = 'instalacion';
}
