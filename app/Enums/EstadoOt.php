<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoOt: string implements HasColor, HasLabel
{
    case Pendiente = 'pendiente';
    case Asignada = 'asignada';
    case EnProceso = 'en_proceso';
    case EnEspera = 'en_espera';
    case CompletadaTecnico = 'completada_tecnico';
    case EnRevision = 'en_revision';
    case Cerrada = 'cerrada';
    case Cancelada = 'cancelada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Asignada => 'Asignada',
            self::EnProceso => 'En proceso',
            self::EnEspera => 'En espera',
            self::CompletadaTecnico => 'Completada por técnico',
            self::EnRevision => 'En revisión',
            self::Cerrada => 'Cerrada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pendiente => 'gray',
            self::Asignada => 'info',
            self::EnProceso => 'primary',
            self::EnEspera => 'warning',
            self::CompletadaTecnico, self::EnRevision => 'purple',
            self::Cerrada => 'success',
            self::Cancelada => 'danger',
        };
    }

    /** @return array<self> */
    public function transiciones(): array
    {
        return match ($this) {
            self::Pendiente => [self::Asignada, self::Cancelada],
            self::Asignada => [self::EnProceso, self::Pendiente, self::Cancelada],
            self::EnProceso => [self::EnEspera, self::CompletadaTecnico, self::Cancelada],
            self::EnEspera => [self::EnProceso, self::Cancelada],
            self::CompletadaTecnico => [self::EnRevision, self::EnProceso, self::Cancelada],
            self::EnRevision => [self::Cerrada, self::EnProceso, self::Cancelada],
            self::Cerrada, self::Cancelada => [],
        };
    }
}
