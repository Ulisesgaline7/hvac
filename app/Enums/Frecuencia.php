<?php

namespace App\Enums;

enum Frecuencia: string
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
}
