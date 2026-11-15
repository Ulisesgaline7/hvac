<?php

namespace App\Http\Controllers;

use App\Services\VerificacionDocumento;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VerificarInformeController extends Controller
{
    public function __invoke(string $token, VerificacionDocumento $documentos)
    {
        // Ruta pública: sin sesión no hay contexto RLS, por eso en Postgres se usa la función de verificación.
        $fila = DB::getDriverName() === 'pgsql'
            ? DB::selectOne('select * from app_verificar_informe(?)', [$token])
            : DB::table('informes as i')
                ->join('ordenes_trabajo as o', 'o.id', '=', 'i.orden_trabajo_id')
                ->join('clientes as c', 'c.id', '=', 'o.cliente_id')
                ->where('i.token_verificacion', $token)->where('i.estado', 'publicado')
                ->first(['o.numero', 'i.publicado_en', 'c.razon_social as cliente', 'i.hash_pdf', 'i.ruta_pdf']);

        if (! $fila) {
            // No es un informe: puede ser garantía, recibo o factura.
            $doc = $documentos->buscar($token);
            if (! $doc) {
                return $this->cotizacion($token);
            }

            [$estado, $color] = $documentos->estado($doc);

            return view('informes.verificacion-documento', [
                'tipo' => ['garantia' => 'Garantía', 'recibo' => 'Recibo de pago', 'factura' => 'Factura'][$doc->tipo] ?? 'Documento',
                'numero' => $doc->numero,
                'cliente' => $doc->cliente,
                'fecha' => Carbon::parse($doc->fecha),
                'vence' => $doc->vence ? Carbon::parse($doc->vence) : null,
                'estado' => $estado,
                'color' => $color,
            ]);
        }

        // Compara el archivo guardado con el hash registrado al firmar.
        // null = informe anterior a la firma digital (no hay hash que comparar).
        $integro = null;
        if (! empty($fila->hash_pdf)) {
            $disco = Storage::disk('local');
            $integro = ! empty($fila->ruta_pdf)
                && $disco->exists($fila->ruta_pdf)
                && hash_equals($fila->hash_pdf, hash_file('sha256', $disco->path($fila->ruta_pdf)));
        }

        return view('informes.verificacion', [
            'numero' => $fila->numero,
            'cliente' => $fila->cliente,
            'publicado' => Carbon::parse($fila->publicado_en),
            'integro' => $integro,
        ]);
    }

    /** Cotización emitida: token propio en la tabla cotizaciones. */
    private function datosCotizacion(string $token): ?object
    {
        return DB::getDriverName() === 'pgsql'
            ? DB::selectOne('select * from app_verificar_cotizacion(?)', [$token])
            : DB::table('cotizaciones as q')
                ->join('clientes as c', 'c.id', '=', 'q.cliente_id')
                ->where('q.token_verificacion', $token)->whereNotNull('q.ruta_pdf')->whereNull('q.deleted_at')
                ->first(['q.numero', 'c.razon_social as cliente', 'q.fecha', 'q.valida_hasta', 'q.total', 'q.estado', 'q.ruta_pdf', 'q.hash_pdf', 'q.emitida_en']);
    }

    private function cotizacion(string $token)
    {
        $fila = $this->datosCotizacion($token);
        abort_if(! $fila, 404);

        $disco = Storage::disk('local');
        $integro = ! empty($fila->hash_pdf) && ! empty($fila->ruta_pdf)
            && $disco->exists($fila->ruta_pdf)
            && hash_equals($fila->hash_pdf, hash_file('sha256', $disco->path($fila->ruta_pdf)));

        $vencida = $fila->valida_hasta && Carbon::parse($fila->valida_hasta)->endOfDay()->isPast();
        [$estado, $color] = match (true) {
            $fila->estado === 'aprobada' => ['Aprobada', '#15803d'],
            $fila->estado === 'enviada' && $vencida => ['Vencida', '#b45309'],
            $fila->estado === 'enviada' => ['Vigente', '#15803d'],
            default => [ucfirst((string) $fila->estado), '#b91c1c'],
        };

        return view('informes.verificacion-cotizacion', [
            'numero' => $fila->numero,
            'cliente' => $fila->cliente,
            'fecha' => $fila->fecha ? Carbon::parse($fila->fecha) : null,
            'valida' => $fila->valida_hasta ? Carbon::parse($fila->valida_hasta) : null,
            'total' => (float) $fila->total,
            'estado' => $estado,
            'color' => $color,
            'integro' => $integro,
            'emitida' => $fila->emitida_en ? Carbon::parse($fila->emitida_en) : null,
            'huella' => substr((string) $fila->hash_pdf, 0, 16),
            'urlPdf' => route('cotizacion.verificada.pdf', $token),
        ]);
    }

    public function cotizacionPdf(string $token)
    {
        $fila = $this->datosCotizacion($token);
        abort_if(! $fila || empty($fila->ruta_pdf) || empty($fila->hash_pdf), 404);

        $disco = Storage::disk('local');
        abort_unless($disco->exists($fila->ruta_pdf)
            && hash_equals($fila->hash_pdf, hash_file('sha256', $disco->path($fila->ruta_pdf))), 404);

        return response()->file($disco->path($fila->ruta_pdf), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="cotizacion-' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) $fila->numero) . '.pdf"',
        ]);
    }
}
