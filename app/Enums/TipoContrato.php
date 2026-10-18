<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoContrato: string implements HasLabel
{
    case PreventivoRecurrente = 'preventivo_recurrente';
    case Instalacion = 'instalacion';
    case Correctivo = 'correctivo';
    case Mixto = 'mixto';

    public function generaCalendario(): bool
    {
        return in_array($this, [self::PreventivoRecurrente, self::Mixto], true);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PreventivoRecurrente => 'Preventivo recurrente',
            self::Instalacion => 'Instalación',
            self::Correctivo => 'Correctivo por evento',
            self::Mixto => 'Mixto',
        };
    }
}
