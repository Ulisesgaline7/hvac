<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait RegistraCreador
{
    public static function bootRegistraCreador(): void
    {
        static::creating(function ($modelo) {
            if (empty($modelo->creado_por) && Auth::check()) {
                $modelo->creado_por = Auth::id();
            }
        });
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
