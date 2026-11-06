<?php

namespace App\Services;

use DomainException;
use Symfony\Component\Process\Process;

class FirmaPdf
{
    private const PYHANKO = '/opt/pyhanko/bin/pyhanko';
    private const DIR = '/etc/hvac-firma';

    /** Firma y certifica el PDF en su lugar; devuelve el SHA-256 del archivo final. */
    public function firmar(string $ruta): string
    {
        if (! is_file($ruta)) {
            throw new DomainException('No existe el PDF a firmar.');
        }

        $salida = $ruta . '.firmado';

        $p = new Process([
            self::PYHANKO, 'sign', 'addsig', '--field', 'Firma1', '--certify',
            'pemder',
            '--key', self::DIR . '/firma.key',
            '--cert', self::DIR . '/firma.crt',
            '--passfile', self::DIR . '/pass.txt',
            $ruta, $salida,
        ]);
        $p->setTimeout(60);
        $p->run();

        if (! $p->isSuccessful() || ! is_file($salida)) {
            @unlink($salida);
            throw new DomainException('No se pudo firmar el PDF. Revise el registro del servidor.');
        }

        rename($salida, $ruta);

        return hash_file('sha256', $ruta);
    }
}
