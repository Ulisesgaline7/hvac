<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VerificarInformeController extends Controller
{
    public function __invoke(string $token)
    {
        // Ruta pública: sin sesión no hay contexto RLS, por eso en Postgres se usa la función de verificación.
        $fila = DB::getDriverName() === 'pgsql'
            ? DB::selectOne('select * from app_verificar_informe(?)', [$token])
            : DB::table('informes as i')
                ->join('ordenes_trabajo as o', 'o.id', '=', 'i.orden_trabajo_id')
                ->join('clientes as c', 'c.id', '=', 'o.cliente_id')
                ->where('i.token_verificacion', $token)->where('i.estado', 'publicado')
                ->first(['o.numero', 'i.publicado_en', 'c.razon_social as cliente', 'i.hash_pdf', 'i.ruta_pdf']);

        abort_if(! $fila, 404);

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
