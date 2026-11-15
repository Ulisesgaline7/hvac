<?php

namespace App\Providers;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mime\Address;

/** Descarta destinatarios reservados/inválidos y envía copia oculta de todo correo saliente. */
class CorreoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(MessageSending::class, function (MessageSending $evento) {
            $m = $evento->message;

            $para = $m->getTo();
            if ($para !== []) {
                $validos = array_values(array_filter($para, fn (Address $a) => ! $this->reservado($a->getAddress())));
                if (count($validos) !== count($para)) {
                    Log::warning('Correo: se omitieron destinatarios no válidos (' . $m->getSubject() . ').');
                }
                if ($validos === []) {
                    return false; // nada que enviar
                }
                if (count($validos) !== count($para)) {
                    $m->to(...$validos);
                }
            }

            $copia = strtolower(trim((string) config('avisos.copia')));
            if ($copia === '') {
                return null;
            }

            $ya = array_map(
                fn (Address $a) => strtolower($a->getAddress()),
                array_merge($m->getTo(), $m->getCc(), $m->getBcc())
            );
            if (! in_array($copia, $ya, true)) {
                $m->addBcc($copia);
            }

            return null;
        });
    }

    private function reservado(string $correo): bool
    {
        $dominio = strtolower(substr((string) strrchr($correo, '@'), 1));
        if ($dominio === '') {
            return true;
        }
        $tld = substr((string) strrchr('.' . $dominio, '.'), 1);

        return in_array($tld, ['test', 'invalid', 'example', 'localhost'], true)
            || in_array($dominio, ['example.com', 'example.org', 'example.net'], true);
    }
}
