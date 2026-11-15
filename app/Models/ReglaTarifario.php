<?php

namespace App\Models;

use App\Models\Concerns\PerteneceADivision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ReglaTarifario extends Model
{
    use PerteneceADivision;

    protected $table = 'reglas_tarifario';

    protected $fillable = ['division_id', 'clave', 'etiqueta', 'grupo', 'tipo', 'valor', 'ayuda', 'orden'];

    protected $casts = ['valor' => 'json'];

    protected static function booted(): void
    {
        static::saved(fn (self $r) => Cache::forget('reglas_tarifario:' . ($r->division_id ?: 'base')));
        static::deleted(fn (self $r) => Cache::forget('reglas_tarifario:' . ($r->division_id ?: 'base')));
    }

    /** [clave => valor guardado]. */
    public static function guardadas(?int $division = null): array
    {
        $division ??= (int) \App\Models\Division::where('codigo', 'CLIMA')->value('id');

        return Cache::rememberForever('reglas_tarifario:' . $division, fn () => static::query()
            ->withoutGlobalScope('division')->where('division_id', $division)->get()
            ->mapWithKeys(fn (self $r) => [$r->clave => $r->valor])->all());
    }

    /** Convierte lo guardado (listas editables) a la forma que usa config('tarifario'). */
    public static function paraConfig(string $clave, mixed $valor): mixed
    {
        $num = function ($v) {
            $f = (float) $v;

            return floor($f) == $f ? (int) $f : $f;
        };

        return match ($clave) {
            'isv' => (float) $valor,
            'servicio_minimo', 'viaticos_dia', 'km_tarifa', 'alimentacion_dia', 'hospedaje_noche' => $num($valor),
            'minimo_si_menos_de', 'validez_dias_habiles', 'viaticos_detallados' => (int) $valor,
            'recargos', 'descuentos' => collect((array) $valor)->mapWithKeys(fn ($r) => [
                $r['codigo'] => array_filter([
                    'nombre' => $r['nombre'] ?? '',
                    'min' => (float) ($r['min'] ?? 0),
                    'max' => (float) ($r['max'] ?? $r['min'] ?? 0),
                    'desde' => isset($r['desde']) && $r['desde'] !== '' ? (int) $r['desde'] : null,
                ], fn ($v) => $v !== null),
            ])->all(),
            'categorias' => collect((array) $valor)->mapWithKeys(fn ($c) => [
                $c['clave'] => [
                    'dias' => (int) ($c['dias'] ?? 0),
                    'corrige' => $c['corrige'] ?? '',
                    'cubre' => $c['cubre'] ?? '',
                    'no_cubre' => $c['no_cubre'] ?? '',
                ],
            ])->all(),
            'condiciones' => array_values(array_filter(array_map('strval', (array) $valor), fn ($t) => trim($t) !== '')),
            default => $valor,
        };
    }

    public function resumen(): string
    {
        $v = $this->valor;

        return match ($this->tipo) {
            'pct' => rtrim(rtrim(number_format((float) $v * 100, 2), '0'), '.') . ' %',
            'monto' => 'L ' . number_format((float) $v, 2),
            'entero' => (string) (int) $v,
            'recargos', 'descuentos' => collect((array) $v)->pluck('codigo')->implode(', '),
            'categorias' => collect((array) $v)->map(fn ($c) => ($c['clave'] ?? '') . ' (' . ($c['dias'] ?? 0) . ' d)')->implode(', '),
            'condiciones' => count((array) $v) . ' condiciones',
            default => (string) json_encode($v),
        };
    }
}
