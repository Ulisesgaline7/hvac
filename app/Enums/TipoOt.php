<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoOt: string implements HasLabel
{
    case Preventivo = 'preventivo';
    case Correctivo = 'correctivo';
    case Instalacion = 'instalacion';

    public function getLabel(): string
    {
        return match ($this) {
            self::Preventivo => 'Preventivo',
            self::Correctivo => 'Correctivo',
            self::Instalacion => 'Instalación',
        };
    }
}
