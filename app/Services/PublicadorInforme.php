<?php

namespace App\Services;

use App\Enums\EstadoOt;
use App\Models\Informe;
use App\Models\OrdenTrabajo;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicadorInforme
{
    public function __construct(private InformeOtService $pdf)
    {
    }

    /** Genera (o regenera) el borrador del informe de una OT cerrada y guarda el PDF en disco privado. */
    public function generar(OrdenTrabajo $ot): Informe
    {
        if ($ot->estado !== EstadoOt::Cerrada) {
            throw new DomainException('Solo se genera el informe de una OT cerrada.');
        }

        $informe = Informe::firstOrNew(['orden_trabajo_id' => $ot->id]);

        if ($informe->estaPublicado()) {
            throw new DomainException('El informe ya está publicado y no se puede regenerar.');
        }

        $informe->estado = 'borrador';
        $informe->token_verificacion ??= Str::random(48);
        $informe->save();

        $ruta = "informes/{$ot->id}/informe-{$ot->numero}.pdf";
        Storage::disk('local')->put($ruta, $this->pdf->pdf($ot->fresh(), $informe));
        $informe->update(['ruta_pdf' => $ruta, 'generado_en' => now()]);

        return $informe;
    }

    public function publicar(Informe $informe): Informe
    {
        $usuario = Auth::user();

        if ($usuario && ! $usuario->hasAnyRole(['administrador', 'coordinador'])) {
            throw new DomainException('Solo coordinador o administrador pueden publicar el informe.');
        }

        if ($informe->estaPublicado()) {
            return $informe;
        }

        if (! $informe->ruta_pdf || ! Storage::disk('local')->exists($informe->ruta_pdf)) {
            throw new DomainException('Genera el informe antes de publicarlo.');
        }

        $informe->update(['estado' => 'publicado', 'publicado_en' => now(), 'publicado_por' => Auth::id()]);

        return $informe;
    }
}
