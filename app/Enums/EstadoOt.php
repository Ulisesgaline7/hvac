<?php

namespace App\Enums;

enum EstadoOt: string
{
    case Pendiente = 'pendiente';
    case Asignada = 'asignada';
    case EnProceso = 'en_proceso';
    case EnEspera = 'en_espera';
    case CompletadaTecnico = 'completada_tecnico';
    case EnRevision = 'en_revision';
    case Cerrada = 'cerrada';
    case Cancelada = 'cancelada';
}
