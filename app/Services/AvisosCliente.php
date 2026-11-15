<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Providers\RlsServiceProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Avisos por correo a clientes. Nunca lanza excepciones. */
final class AvisosCliente
{
    /** @return array<string,string> correo => nombre */
    public static function destinatarios(int $clienteId): array
    {
        return RlsServiceProvider::comoInterno(function () use ($clienteId) {
            $out = [];

            $json = DB::table('clientes')->where('id', $clienteId)->value('contactos');
            $contactos = is_string($json) ? json_decode($json, true) : (array) $json;
            foreach ((array) $contactos as $k) {
                $mail = strtolower(trim((string) ($k['correo'] ?? '')));
                if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                    $out[$mail] = (string) ($k['nombre'] ?? '');
                }
            }

            if (! $out) {
                foreach (DB::table('users')->where('cliente_id', $clienteId)->get(['name', 'email']) as $u) {
                    $mail = strtolower(trim((string) $u->email));
                    if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                        $out[$mail] = (string) $u->name;
                    }
                }
            }

            return $out;
        });
    }

    /** Desglose en texto, una línea por párrafo para que el correo lo muestre ordenado. */
    private static function desglose(Cotizacion $c): string
    {
        $m = fn ($v) => 'L ' . number_format((float) $v, 2);
        $rec = collect($c->recargos ?? [])->map(fn ($v, $k) => config('tarifario.recargos.' . (is_int($k) ? $v : $k) . '.nombre'))->filter()->values()->implode(', ');
        $desc = $c->descuento_codigo ? config('tarifario.descuentos.' . $c->descuento_codigo . '.nombre') : null;
        $pct = rtrim(rtrim(number_format((float) $c->descuento_pct * 100, 2), '0'), '.');

        $l = ['Servicios: ' . $m($c->subtotal_servicios)];
        if ((float) $c->ajuste_minimo > 0) { $l[] = 'Ajuste a servicio mínimo por visita: ' . $m($c->ajuste_minimo); }
        if ((float) $c->recargos_monto > 0) { $l[] = 'Recargos (' . $rec . '): ' . $m($c->recargos_monto); }
        if ((float) $c->descuento_monto > 0) { $l[] = 'Descuento (' . $desc . ', ' . $pct . ' %): − ' . $m($c->descuento_monto); }
        if ((float) $c->repuestos_monto > 0) { $l[] = 'Repuestos y materiales: ' . $m($c->repuestos_monto); }
        if ((float) $c->logistica_monto > 0) { $l[] = 'Logística: ' . $m($c->logistica_monto); }
        if ((float) $c->viaticos_monto > 0) { $l[] = 'Viáticos: ' . $m($c->viaticos_monto); }
        if ($c->visita_tratamiento === 'cobrar' && (float) $c->visita_monto > 0) { $l[] = 'Visita técnica: ' . $m($c->visita_monto); }
        $l[] = 'Subtotal: ' . $m($c->subtotal);
        $l[] = 'ISV (' . (int) (config('tarifario.isv') * 100) . ' %): ' . $m($c->isv);
        $l[] = 'TOTAL: ' . $m($c->total);
        if ($ra = ResumenAnual::resumen($c)) {
            $l[] = 'Plan anual: ' . $ra['texto'] . ' (los valores de arriba son por visita)';
            $l[] = 'Subtotal anual: ' . $m($ra['subtotal']);
            $l[] = 'ISV anual: ' . $m($ra['isv']);
            $l[] = 'TOTAL ANUAL: ' . $m($ra['total']);
        }

        return implode("\n\n", $l);
    }

    /** Envía el contrato (PDF adjunto + enlace de firma) a los contactos del cliente. Devuelve cuántos correos salieron. */
    public static function contratoEnviado(\App\Models\Contrato $c): int
    {
        try {
            if (! $c->token_firma) {
                return 0;
            }

            $cliente = (string) RlsServiceProvider::comoInterno(
                fn () => DB::table('clientes')->where('id', $c->cliente_id)->value('razon_social')
            );

            $url = route('contratos.firma', $c->token_firma);

            $adjuntos = [];
            try {
                $dir = storage_path('app/contratos');
                if (! is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                $ruta = $dir . '/contrato-' . $c->id . '.pdf';
                $bytes = RlsServiceProvider::comoInterno(fn () => app(ContratoPdf::class)->render($c));
                file_put_contents($ruta, $bytes);
                $adjuntos[] = ['ruta' => $ruta, 'nombre' => 'contrato-' . $c->id . '.pdf', 'mime' => 'application/pdf'];
            } catch (\Throwable $e) {
                Log::warning('PDF de contrato para el correo falló: ' . $e->getMessage());
            }

            $enviados = 0;
            foreach (self::destinatarios((int) $c->cliente_id) as $correo => $nombre) {
                $ok = NotificadorCorreo::enviar('contrato_enviado', $correo, [
                    'nombre' => $nombre ?: $cliente,
                    'cliente' => $cliente,
                    'contrato' => '#' . $c->id,
                    'vigencia' => ($c->vigencia_inicio ? Carbon::parse($c->vigencia_inicio)->format('d/m/Y') : '-')
                        . ' al ' . ($c->vigencia_fin ? Carbon::parse($c->vigencia_fin)->format('d/m/Y') : '-'),
                ], $url, 'contrato-firma-' . $c->id . '-' . ($c->firma_enviada_at ? Carbon::parse($c->firma_enviada_at)->timestamp : 0), $adjuntos, 'Revisar y firmar');
                if ($ok) {
                    $enviados++;
                }
            }

            return $enviados;
        } catch (\Throwable $e) {
            Log::warning('Aviso de contrato enviado falló: ' . $e->getMessage());

            return 0;
        }
    }

    public static function cotizacionEnviada(Cotizacion $c): void
    {
        try {
            if (! $c->token_aceptacion) {
                return;
            }

            $cliente = (string) RlsServiceProvider::comoInterno(
                fn () => DB::table('clientes')->where('id', $c->cliente_id)->value('razon_social')
            );

            $url = route('cotizaciones.aceptacion', $c->token_aceptacion);

            // PDF en disco: el correo va por cola y el job lo lee al ejecutarse.
            $adjuntos = [];
            try {
                $dir = storage_path('app/cotizaciones');
                if (! is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                $ruta = $dir . '/' . $c->numero . '.pdf';
                $bytes = RlsServiceProvider::comoInterno(fn () => app(CotizacionPdf::class)->render($c));
                file_put_contents($ruta, $bytes);
                $adjuntos[] = ['ruta' => $ruta, 'nombre' => $c->numero . '.pdf', 'mime' => 'application/pdf'];
            } catch (\Throwable $e) {
                Log::warning('PDF de cotización para el correo falló: ' . $e->getMessage());
            }

            foreach (self::destinatarios((int) $c->cliente_id) as $correo => $nombre) {
                NotificadorCorreo::enviar('cotizacion_enviada', $correo, [
                    'nombre' => $nombre ?: $cliente,
                    'cliente' => $cliente,
                    'cotizacion' => (string) $c->numero,
                    'total' => 'L ' . number_format((float) $c->total, 2),
                    'desglose' => self::desglose($c),
                    'valida_hasta' => $c->valida_hasta ? Carbon::parse($c->valida_hasta)->format('d/m/Y') : '-',
                ], $url, 'cot-enviada-' . $c->id, $adjuntos, 'Revisar, aceptar o rechazar');
            }

            // Aviso dentro del portal (campanita) a los usuarios del cliente.
            try {
                $usuarios = RlsServiceProvider::comoInterno(
                    fn () => \App\Models\User::where('cliente_id', $c->cliente_id)->get()
                );
                foreach ($usuarios as $u) {
                    $u->notify(new \App\Notifications\AvisoPanel(
                        'Nueva cotización ' . $c->numero,
                        'Total L ' . number_format((float) $c->total, 2) . '. Revísela, acéptela o recházela.',
                        $url,
                        false,
                        'heroicon-o-document-text'
                    ));
                }
            } catch (\Throwable $e) {
                Log::warning('Aviso de panel de cotización falló: ' . $e->getMessage());
            }
        } catch (\Throwable $e) {
            Log::warning('Aviso de cotización enviada falló: ' . $e->getMessage());
        }
    }
}
