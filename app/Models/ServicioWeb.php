<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicioWeb extends Model
{
    protected $table = 'servicios_web';

    protected $fillable = ['nombre', 'descripcion', 'precio_desde', 'moneda', 'unidad', 'imagen', 'orden', 'visible'];

    protected $casts = ['precio_desde' => 'decimal:2', 'visible' => 'boolean'];
}
