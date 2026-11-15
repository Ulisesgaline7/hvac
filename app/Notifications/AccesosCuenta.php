<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccesosCuenta extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $password,
        public string $url,
        public bool $reenvio = false,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(($this->reenvio ? 'Nuevos accesos' : 'Tus accesos') . ' a ' . \App\Support\Marca::nombre())
            ->greeting('Hola ' . $notifiable->name)
            ->line($this->reenvio ? 'Se generó una contraseña nueva para tu cuenta.' : 'Se creó tu cuenta.')
            ->line('Usuario: ' . $notifiable->email)
            ->line('Contraseña temporal: ' . $this->password)
            ->action('Entrar', $this->url)
            ->line('Por seguridad, el sistema te pedirá crear tu propia contraseña en el primer ingreso.');
    }
}
