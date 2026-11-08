<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/** Documento de un tercero (fabricante, proveedor, gestor autorizado): se carga y se conserva tal cual. */
class DocumentoTercero extends Model implements HasMedia
{
    use Auditable, InteractsWithMedia, RegistraCreador, SoftDeletes;

    protected $table = 'documentos_terceros';
    protected $guarded = [];
    protected $casts = ['emitido_en' => 'date', 'vigente_hasta' => 'date', 'revisar_en' => 'date'];

    public const TIPOS = [
        'fds' => 'Ficha de datos de seguridad (FDS/SDS)',
        'ficha_tecnica' => 'Ficha técnica',
        'certificado_analisis' => 'Certificado de análisis o calidad del lote',
        'manual' => 'Manual del fabricante',
        'certificado_gestor' => 'Certificado de gestor autorizado',
        'otro' => 'Otro',
    ];

    /** Tipos que se revisan periódicamente aunque no tengan vencimiento propio. */
    private const REVISION_ANIOS = ['fds' => 2, 'ficha_tecnica' => 2];

    protected static function booted(): void
    {
        static::saving(function (self $d) {
            if (! $d->revisar_en && $d->emitido_en && isset(self::REVISION_ANIOS[$d->tipo])) {
                $d->revisar_en = $d->emitido_en->copy()->addYears(self::REVISION_ANIOS[$d->tipo]);
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('archivo')->singleFile()->useDisk('local')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
    }

    public function scopeVencidos(Builder $q): Builder
    {
        return $q->where(fn ($w) => $w->whereDate('vigente_hasta', '<', now())->orWhereDate('revisar_en', '<', now()));
    }

    public function scopePorVencer(Builder $q, int $dias = 30): Builder
    {
        return $q->where(fn ($w) => $w->whereBetween('vigente_hasta', [now()->toDateString(), now()->addDays($dias)->toDateString()])
            ->orWhereBetween('revisar_en', [now()->toDateString(), now()->addDays($dias)->toDateString()]));
    }

    public function refrigerante(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Refrigerante::class); }
    public function cilindro(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(CilindroRefrigerante::class, 'cilindro_id'); }
    public function insumo(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Insumo::class); }
    public function equipo(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Equipo::class); }
    public function ordenTrabajo(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(OrdenTrabajo::class, 'orden_trabajo_id'); }
    public function proveedor(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Proveedor::class); }

    /** Texto corto de a qué está ligado el documento. */
    public function aplicaA(): string
    {
        return collect([
            $this->refrigerante ? 'Refrigerante ' . $this->refrigerante->nombre : null,
            $this->cilindro ? 'Cilindro ' . $this->cilindro->codigo : null,
            $this->insumo ? 'Insumo ' . $this->insumo->nombre : null,
            $this->equipo ? 'Equipo ' . $this->equipo->codigo : null,
            $this->ordenTrabajo ? 'OT ' . $this->ordenTrabajo->numero : null,
        ])->filter()->implode(' · ') ?: 'General';
    }
}
