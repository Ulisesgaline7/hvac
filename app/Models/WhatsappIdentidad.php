<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappIdentidad extends Model
{
    protected $table = 'whatsapp_identidades';
    protected $guarded = [];
    protected $hidden = ['pin_hash'];

    protected $casts = [
        'acepta' => 'boolean',
        'acepta_en' => 'datetime',
        'pin_bloqueado_hasta' => 'datetime',
        'pin_cambiado_en' => 'datetime',
    ];

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }
}
