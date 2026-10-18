<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoContrato: string implements HasColor, HasLabel
{
    case Borrador = 'borrador';
    case Activo = 'activo';
    case Vencido = 'vencido';
    case Cancelado = 'cancelado';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Borrador => 'gray',
            self::Activo => 'success',
            self::Vencido => 'warning',
            self::Cancelado => 'danger',
        };
    }
}
