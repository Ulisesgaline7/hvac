<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Correo ya renderizado desde una plantilla, para destinatarios sin usuario (clientes, visitantes de la web). */
class CorreoPlantilla extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public function __construct(
        public string $titulo,
        public string $cuerpo,
        public ?string $url = null,
        public ?string $nombre = null,
        public ?string $textoBoton = null,
        /** @var array<int,array{ruta:string,nombre:string,mime:string}> */
        public array $adjuntos = [],
        /** @var array<int,array{texto:string,url:string}> enlaces extra bajo el botón principal */
        public array $enlaces = [],
    ) {}

    /** Espera creciente entre reintentos, en segundos. */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $m = (new MailMessage)
            ->subject($this->titulo)
            ->greeting($this->nombre ? 'Hola, ' . $this->nombre : 'Hola');

        foreach (preg_split('/\R{2,}/', trim($this->cuerpo)) as $parrafo) {
            $m->line(trim($parrafo));
        }

        if ($this->url) {
            $m->action($this->textoBoton ?: 'Ver detalle', $this->url);
        }

        foreach ($this->adjuntos as $a) {
            if (is_file($a['ruta'])) {
                $m->attach($a['ruta'], ['as' => $a['nombre'], 'mime' => $a['mime']]);
            }
        }

        return $m->salutation("Saludos,\nControl Total");
    }
}
