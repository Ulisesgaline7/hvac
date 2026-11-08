<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Token, URL y QR de verificación pública para garantías, recibos y facturas. */
class VerificacionDocumento
{
    public function __construct(private GeneradorQr $qr)
    {
    }

    public function token(Model $modelo): string
    {
        if (! $modelo->token_verificacion) {
            $modelo->forceFill(['token_verificacion' => Str::random(48)])->saveQuietly();
        }

        return $modelo->token_verificacion;
    }

    public function url(Model $modelo): string
    {
        return url('/v/' . $this->token($modelo));
    }

    /** Data URI del QR, o null si no se pudo generar (el PDF sale igual, sin QR). */
    public function qr(Model $modelo): ?string
    {
        try {
            return $this->qr->dataUriDeUrl($this->url($modelo));
        } catch (\Throwable) {
            return null;
        }
    }

    public function buscar(string $token): ?object
    {
        if (DB::getDriverName() === 'pgsql') {
            return DB::selectOne('select * from app_verificar_documento(?)', [$token]);
        }

        $g = DB::table('garantias as g')
            ->join('ordenes_trabajo as o', 'o.id', '=', 'g.orden_trabajo_id')
            ->join('clientes as c', 'c.id', '=', 'o.cliente_id')
            ->where('g.token_verificacion', $token)
            ->first(['g.numero', 'c.razon_social as cliente', 'g.desde as fecha', 'g.hasta as vence', 'g.estado']);
        if ($g) {
            $g->tipo = 'garantia';

            return $g;
        }

        $p = DB::table('pagos as p')->join('clientes as c', 'c.id', '=', 'p.cliente_id')
            ->where('p.token_verificacion', $token)
            ->first(['p.numero', 'c.razon_social as cliente', 'p.fecha']);
        if ($p) {
            $p->tipo = 'recibo';
            $p->vence = null;
            $p->estado = 'vigente';

            return $p;
        }

        $f = DB::table('facturas as f')->join('clientes as c', 'c.id', '=', 'f.cliente_id')
            ->where('f.token_verificacion', $token)
            ->first(['f.numero', 'c.razon_social as cliente', 'f.fecha', 'f.vence', 'f.estado']);
        if ($f) {
            $f->tipo = 'factura';
        }

        return $f;
    }

    /** @return array{0:string,1:string} [texto, color] */
    public function estado(object $d): array
    {
        if (in_array($d->estado, ['anulada', 'anulado'], true)) {
            return ['Anulado', '#b91c1c'];
        }
        if ($d->tipo === 'garantia' && $d->vence && \Illuminate\Support\Carbon::parse($d->vence)->endOfDay()->isPast()) {
            return ['Vencida', '#b45309'];
        }

        return ['Vigente', '#15803d'];
    }
}
