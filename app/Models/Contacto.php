<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contacto extends Model
{
    protected $fillable = ['nombre', 'telefono', 'email', 'ciudad', 'servicio', 'mensaje', 'atendido', 'ip'];
}
