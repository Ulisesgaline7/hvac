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
        return $this->correo ? ['database', 'mail'] : ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $n = FilamentNotification::make()
            ->title($this->titulo)
            ->body($this->cuerpo)
            ->icon($this->icono);

        if ($this->url) {
            $n->actions([Action::make('ver')->label('Ver')->url($this->url)->button()]);
        }

        return $n->getDatabaseMessage();
    }

    public function toMail($notifiable): MailMessage
    {
        $m = (new MailMessage)->subject($this->titulo)->line($this->cuerpo);

        if ($this->url) {
            $m->action('Ver detalle', $this->url);
        }

        return $m;
    }
}
