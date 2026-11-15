<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentoColaborador extends Model
{
    use Auditable, RegistraCreador, SoftDeletes;

    protected $table = 'documentos_colaborador';
    protected $guarded = [];

    protected $casts = ['vencimiento' => 'date'];

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }
}
