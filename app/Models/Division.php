<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Division extends Model
{
    use Auditable;

    protected $table = 'divisiones';
    protected $guarded = [];

    protected $casts = ['activa' => 'boolean'];

    public function scopeActivas(Builder $q): Builder
    {
        return $q->where('activa', true)->orderBy('orden');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function tecnicos(): BelongsToMany
    {
        return $this->belongsToMany(Tecnico::class, 'tecnico_division')
            ->withPivot(['habilitado', 'vencimiento', 'notas'])->withTimestamps();
    }
}
