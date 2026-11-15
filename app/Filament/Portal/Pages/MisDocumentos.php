<?php

namespace App\Filament\Portal\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MisDocumentos extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderArrowDown;

    protected static ?string $navigationLabel = 'Mis documentos';

    protected static ?string $title = 'Mis documentos';

    protected static ?string $slug = 'mis-documentos';

    protected string $view = 'filament.portal.pages.mis-documentos';

    public function documentos(): array
    {
        $c = auth()->user()?->cliente_id;
        if (! $c) {
            return [];
        }

        $f = fn ($d) => $d ? Carbon::parse($d)->format('d/m/Y') : '';
        $filas = [];

        foreach (DB::table('contratos')->where('cliente_id', $c)->whereIn('estado', ['activo', 'vencido'])->get() as $r) {
            $filas[] = ['Contrato', 'N.º ' . str_pad((string) $r->id, 5, '0', STR_PAD_LEFT), $f($r->vigencia_inicio), route('portal.doc.contrato', $r->id), $r->vigencia_inicio];
        }
        foreach (DB::table('cotizaciones')->where('cliente_id', $c)->whereIn('estado', ['enviada', 'aprobada'])->whereNull('deleted_at')->get() as $r) {
            $filas[] = ['Cotización', $r->numero, $f($r->fecha), route('portal.doc.cotizacion', $r->id), $r->fecha];
        }
        foreach (DB::table('facturas')->where('cliente_id', $c)->where('estado', '!=', 'borrador')->get() as $r) {
            $filas[] = ['Factura', $r->numero, $f($r->fecha), route('portal.doc.factura', $r->id), $r->fecha];
        }
        foreach (DB::table('pagos')->where('cliente_id', $c)->get() as $r) {
            $filas[] = ['Recibo de pago', $r->numero, $f($r->fecha), route('portal.doc.recibo', $r->id), $r->fecha];
        }
        foreach (DB::table('informes')->join('ordenes_trabajo', 'ordenes_trabajo.id', '=', 'informes.orden_trabajo_id')
            ->where('ordenes_trabajo.cliente_id', $c)->where('informes.estado', 'publicado')
            ->get(['informes.orden_trabajo_id', 'ordenes_trabajo.numero', 'informes.publicado_en']) as $r) {
            $filas[] = ['Informe de servicio', $r->numero, $f($r->publicado_en), route('ordenes-trabajo.informe', $r->orden_trabajo_id), $r->publicado_en];
        }
        foreach (DB::table('garantias')->join('ordenes_trabajo', 'ordenes_trabajo.id', '=', 'garantias.orden_trabajo_id')
            ->where('ordenes_trabajo.cliente_id', $c)->whereIn('ordenes_trabajo.estado', ['completada_tecnico', 'en_revision', 'cerrada'])
            ->get(['garantias.orden_trabajo_id', 'garantias.numero', 'garantias.desde']) as $r) {
            $filas[] = ['Certificado de garantía', $r->numero, $f($r->desde), route('ordenes-trabajo.entregable', [$r->orden_trabajo_id, 'garantia']), $r->desde];
        }

        usort($filas, fn ($a, $b) => strcmp((string) $b[4], (string) $a[4]));

        return $filas;
    }
}
