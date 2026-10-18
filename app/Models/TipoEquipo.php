<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoEquipo extends Model
{
    use Auditable, RegistraCreador, SoftDeletes;

    protected $table = 'tipos_equipo';
    protected $guarded = [];
}
