<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicioCatalogo extends Model
{
    protected $table = 'servicios_catalogo';
    protected $guarded = [];

    protected $casts = ['precio' => 'decimal:2', 'precio_hasta' => 'decimal:2', 'cuenta_equipo' => 'boolean', 'activo' => 'boolean'];
}
