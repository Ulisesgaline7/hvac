<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudCliente extends Model
{
    protected $table = 'solicitudes_cliente';

    protected $fillable = ['cliente_id', 'sucursal_id', 'tipo', 'orden_trabajo_id', 'garantia_id', 'equipo_id', 'descripcion', 'estado', 'creado_por'];
}
