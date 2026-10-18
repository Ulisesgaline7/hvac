<?php

namespace App\Enums;

enum TipoContrato: string
{
    case PreventivoRecurrente = 'preventivo_recurrente';
    case Instalacion = 'instalacion';
    case Correctivo = 'correctivo';
    case Mixto = 'mixto';

    public function generaCalendario(): bool
    {
        return in_array($this, [self::PreventivoRecurrente, self::Mixto], true);
    }
}
