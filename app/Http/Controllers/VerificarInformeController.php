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
            abort_if(! $doc, 404);

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
}
