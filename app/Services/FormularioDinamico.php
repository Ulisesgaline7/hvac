<?php

namespace App\Services;

use App\Forms\Components\FirmaPad;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;

class FormularioDinamico
{
    /** Tipos que aún no se renderizan ni validan (esperan el módulo de adjuntos). */
    public const PENDIENTES = [];

    public static function componentes(array $campos, string $prefijo = 'r_'): array
    {
        $salida = [];

        foreach ($campos as $c) {
            if (in_array($c['tipo'], self::PENDIENTES, true)) {
                continue;
            }

            $nombre = $prefijo . $c['clave'];
            $etiqueta = $c['etiqueta'] . (! empty($c['obligatorio']) ? ' *' : '');

            $salida[] = match ($c['tipo']) {
                'numero' => TextInput::make($nombre)->label($etiqueta)->numeric()
                    ->suffix($c['unidad'] ?? null)->helperText(self::ayudaRango($c)),
                'seleccion' => Select::make($nombre)->label($etiqueta)->options(self::opciones($c)),
                'casilla' => Toggle::make($nombre)->label($etiqueta),
                'checklist' => CheckboxList::make($nombre)->label($etiqueta)->options(self::opciones($c))->columns(2)->columnSpanFull(),
                'foto' => FileUpload::make('f_' . $c['clave'])->label($etiqueta)->image()->multiple()->maxFiles(6)
                    ->maxSize(10240)->disk('local')->directory('tmp-fotos')->visibility('private')->columnSpanFull(),
                'firma' => FirmaPad::make('f_' . $c['clave'])->label($etiqueta)->columnSpanFull(),
                'fecha' => DatePicker::make($nombre)->label($etiqueta),
                'nota' => Textarea::make($nombre)->label($etiqueta)->columnSpanFull(),
                default => TextInput::make($nombre)->label($etiqueta),
            };
        }

        return $salida;
    }

    /** Etiquetas de los campos obligatorios sin respuesta. `false` en una casilla cuenta como respondido. */
    public static function faltantes(array $campos, array $respuestas): array
    {
        $faltan = [];

        foreach ($campos as $c) {
            if (empty($c['obligatorio'])) {
                continue;
            }

            $v = $respuestas[$c['clave']] ?? null;
            if ($v === null || $v === '' || $v === []) {
                $faltan[] = $c['etiqueta'];
            }
        }

        return $faltan;
    }

    /** Mensajes para valores numéricos fuera del rango esperado. No bloquean el guardado. */
    public static function fueraDeRango(array $campos, array $respuestas): array
    {
        $alertas = [];

        foreach ($campos as $c) {
            $v = $respuestas[$c['clave']] ?? null;
            if ($c['tipo'] !== 'numero' || $v === null || $v === '' || ! is_numeric($v)) {
                continue;
            }

            $min = $c['min'] ?? null;
            $max = $c['max'] ?? null;

            if (($min !== null && $min !== '' && $v < $min) || ($max !== null && $max !== '' && $v > $max)) {
                $alertas[] = "{$c['etiqueta']}: {$v} fuera del rango esperado (" . ($min ?? '…') . ' a ' . ($max ?? '…') . ')';
            }
        }

        return $alertas;
    }

    private static function ayudaRango(array $c): ?string
    {
        $min = $c['min'] ?? null;
        $max = $c['max'] ?? null;

        return ($min === null || $min === '') && ($max === null || $max === '')
            ? null
            : 'Rango esperado: ' . (($min ?? '') === '' ? '…' : $min) . ' a ' . (($max ?? '') === '' ? '…' : $max);
    }

    private static function opciones(array $c): array
    {
        return collect($c['opciones'] ?? [])->mapWithKeys(fn ($o) => [$o => $o])->all();
    }
}
