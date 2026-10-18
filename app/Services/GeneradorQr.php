<?php

namespace App\Services;

use App\Models\Equipo;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

class GeneradorQr
{
    public function urlDe(Equipo $equipo): string
    {
        return url('/e/' . $equipo->token_qr);
    }

    public function dataUri(Equipo $equipo): string
    {
        $escritor = extension_loaded('gd') ? new PngWriter() : new SvgWriter();

        return (new Builder(
            writer: $escritor,
            data: $this->urlDe($equipo),
            size: 300,
            margin: 0,
        ))->build()->getDataUri();
    }
}
