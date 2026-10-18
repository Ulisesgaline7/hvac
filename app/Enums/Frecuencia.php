<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Frecuencia: string implements HasLabel
{
    case Mensual = 'mensual';
    case Trimestral = 'trimestral';
    case Semestral = 'semestral';
    case Anual = 'anual';

    public function meses(): int
    {
        return match ($this) {
            self::Mensual => 1,
            self::Trimestral => 3,
            self::Semestral => 6,
            self::Anual => 12,
        };
    }

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }
}
