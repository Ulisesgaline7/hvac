<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Providers\RlsServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Avisos por correo al equipo interno. Nunca lanza excepciones. */
final class AvisosInternos
{
    /** Correo fijo que recibe siempre el aviso, además del vendedor. */
    private const CORREO_VENTAS = 'controltotalhn@gmail.com';

    /** $resultado: 'aceptada' o 'rechazada'. $detalle: quién firmó o el motivo. */
    public static function cotizacionRespondida(int $cotizacionId, string $resultado, string $detalle = ''): void
    {
        try {
            [$c, $cliente, $vendedor] = RlsServiceProvider::comoInterno(function () use ($cotizacionId) {
                $c = Cotizacion::find($cotizacionId);
                if (! $c) {
                    return [null, '', null];
                }
                $cliente = (string) DB::table('clientes')->where('id', $c->cliente_id)->value('razon_social');
                $vendedor = $c->vendedor_id ? DB::table('users')->where('id', $c->vendedor_id)->first(['name', 'email']) : null;

                return [$c, $cliente, $vendedor];
            });

            if (! $c) {
                return;
            }

            $destinos = [strtolower(self::CORREO_VENTAS) => 'Ventas'];
            if ($vendedor && filter_var($vendedor->email, FILTER_VALIDATE_EMAIL)) {
                $destinos[strtolower(trim($vendedor->email))] = (string) $vendedor->name;
            }

            foreach ($destinos as $correo => $nombre) {
                NotificadorCorreo::enviar('cotizacion_respondida', $correo, [
                    'nombre' => $nombre,
                    'cliente' => $cliente,
                    'cotizacion' => (string) $c->numero,
                    'resultado' => $resultado,
                    'total' => 'L ' . number_format((float) $c->total, 2),
                    'detalle' => $detalle !== '' ? $detalle : 'Sin comentarios.',
                ], null, 'cot-resp-' . $c->id . '-' . $resultado);
            }
        } catch (\Throwable $e) {
            Log::warning('Aviso interno de cotización respondida falló: ' . $e->getMessage());
        }
    }
}
