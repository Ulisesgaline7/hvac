<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentoTecnico extends Model
{
    use Auditable, RegistraCreador, SoftDeletes;

    protected $table = 'documentos_tecnico';
    protected $guarded = [];

    protected $casts = ['vencimiento' => 'date'];

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }
}
