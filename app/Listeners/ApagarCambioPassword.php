<?php

namespace App\Listeners;

use Illuminate\Auth\Events\PasswordReset;

class ApagarCambioPassword
{
    public function handle(PasswordReset $event): void
    {
        if ($event->user->debe_cambiar_password) {
            $event->user->forceFill(['debe_cambiar_password' => false])->save();
        }
    }
}
