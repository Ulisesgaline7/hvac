<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Frecuencia: string implements HasLabel
{
    case Mensual = 'mensual';
    case Trimestral = 'trimestral';
    case Semestral = 'semestral';
    case Anual = 'anual';
    case Unica = 'unica';

    public function meses(): int
    {
        return match ($this) {
            self::Mensual => 1,
            self::Trimestral => 3,
            self::Semestral => 6,
            self::Anual => 12,
            self::Unica => 0, // no se repite; GeneradorCalendario::fechas la trata aparte
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Unica => 'Única (una sola vez)',
            default => ucfirst($this->value),
        };
    }
}
