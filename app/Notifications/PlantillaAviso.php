<?php

namespace App\Notifications;

use App\Notifications\Channels\WebPushChannel;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Aviso ya renderizado desde una plantilla del admin. Campana + correo + push, según la plantilla. */
class PlantillaAviso extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $titulo,
        public string $cuerpo,
        public string $pushTitulo,
        public string $pushCuerpo,
        public ?string $url = null,
        public bool $correo = true,
        public bool $push = true,
        public string $icono = 'heroicon-o-banknotes',
    ) {}

    public function via($notifiable): array
    {
        $canales = ['database'];
        if ($this->correo) {
            $canales[] = 'mail';
        }
        if ($this->push) {
            $canales[] = WebPushChannel::class;
        }

        return $canales;
    }

    public function toPush($notifiable): array
    {
        return ['title' => $this->pushTitulo, 'body' => $this->pushCuerpo, 'url' => $this->url ?: '/tecnico'];
    }

    public function toDatabase($notifiable): array
    {
        $n = FilamentNotification::make()->title($this->titulo)->body($this->cuerpo)->icon($this->icono);

        if ($this->url) {
            $n->actions([Action::make('ver')->label('Ver')->url($this->url)->button()->markAsRead()]);
        }

        return $n->getDatabaseMessage();
    }

    public function toMail($notifiable): MailMessage
    {
        $m = (new MailMessage)
            ->subject($this->titulo)
            ->greeting('Hola, ' . ($notifiable->name ?? ''));

        foreach (preg_split('/\R{2,}/', trim($this->cuerpo)) as $parrafo) {
            $m->line(trim($parrafo));
        }

        if ($this->url) {
            $m->action('Ver detalle', $this->url);
        }

        return $m->salutation("Saludos,\nControl Total");
    }
}
