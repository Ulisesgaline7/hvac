<?php

namespace App\Notifications;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AvisoPanel extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $titulo,
        public string $cuerpo = '',
        public ?string $url = null,
        public bool $correo = false,
        public string $icono = 'heroicon-o-bell',
    ) {}

    public function via($notifiable): array
    {
        $canales = $this->correo ? ['database', 'mail'] : ['database'];
        $canales[] = \App\Notifications\Channels\WebPushChannel::class;
        $canales[] = 'broadcast';

        return $canales;
    }

    /** Texto genérico: se ve en la pantalla bloqueada. El detalle está en el panel. */
    public function toPush($notifiable): array
    {
        return ['title' => 'Control Total', 'body' => 'Tienes un aviso nuevo', 'url' => $this->url ?: '/tecnico'];
    }

    public function toDatabase($notifiable): array
    {
        $n = FilamentNotification::make()
            ->title($this->titulo)
            ->body($this->cuerpo)
            ->icon($this->icono);

        if ($this->url) {
            $n->actions([Action::make('ver')->label('Ver')->url($this->url)->button()->markAsRead()->markAsRead()]);
        }

        return $n->getDatabaseMessage();
    }

    public function toBroadcast($notifiable): \Illuminate\Notifications\Messages\BroadcastMessage
    {
        return FilamentNotification::make()
            ->title($this->titulo)
            ->body($this->cuerpo)
            ->icon($this->icono)
            ->getBroadcastMessage();
    }

    public function toMail($notifiable): MailMessage
    {
        $m = (new MailMessage)
            ->subject($this->titulo)
            ->greeting('Hola, ' . ($notifiable->name ?? ''))
            ->line($this->cuerpo)
            ->salutation("Saludos,\nControl Total");

        if ($this->url) {
            $m->action('Ver detalle', $this->url);
        }

        return $m;
    }
}
