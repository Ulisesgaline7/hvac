<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use App\Observers\TecnicoObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(TecnicoObserver::class)]
class Tecnico extends Model
{
    use Auditable, RegistraCreador, SoftDeletes;

    protected $table = 'tecnicos';
    protected $guarded = [];

    protected $casts = ['especialidades' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoTecnico::class);
    }

    public function divisiones(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Division::class, 'tecnico_division')
            ->withPivot(['habilitado', 'vencimiento', 'notas'])->withTimestamps();
    }

    public function aceptaciones(): HasMany
    {
        return $this->hasMany(AceptacionTecnico::class);
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(OrdenTrabajo::class);
    }
}
