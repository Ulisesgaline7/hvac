<?php

namespace App\Enums;

enum EstadoEquipo: string
{
    case Operativo = 'operativo';
    case ConObservaciones = 'con_observaciones';
    case FueraDeServicio = 'fuera_de_servicio';
    case DadoDeBaja = 'dado_de_baja';
}
