<?php

namespace App\Enums;

enum Rol: string
{
    case Administrador = 'administrador';
    case Coordinador = 'coordinador';
    case Tecnico = 'tecnico';
    case ClienteAdmin = 'cliente_admin';
    case ClienteConsulta = 'cliente_consulta';
}
