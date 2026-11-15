<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappConversacion extends Model
{
    protected $table = 'whatsapp_conversaciones';
    protected $guarded = [];
    protected $casts = ['contexto' => 'array', 'expira_en' => 'datetime'];
}
