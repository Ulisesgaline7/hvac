<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoVersion extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'documento_versiones';
    protected $guarded = [];
}
