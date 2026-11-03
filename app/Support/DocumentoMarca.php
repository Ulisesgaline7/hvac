<?php

namespace App\Support;

use App\Models\AjusteDocumento;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/** Personalización de los PDF. Si un ajuste está vacío se usa el valor por defecto. */
final class DocumentoMarca
{
    public static function defaults(): array
    {
        $e = config('cotizacion.empresa', []);

        return [
            'logo' => null,
            'color_primario' => Marca::PRIMARIO,
            'color_oscuro' => Marca::OSCURO,
            'color_acento' => '#00D1E8',
            'empresa_nombre' => $e['nombre'] ?? 'Control Total',
            'empresa_rtn' => $e['rtn'] ?? '',
            'empresa_telefono' => $e['telefono'] ?? '',
            'empresa_correo' => $e['correo'] ?? '',
            'empresa_direccion' => $e['direccion'] ?? '',
            'pie_texto' => 'Control Total · Refrigeración y Aire Acondicionado',
        ];
    }

    public static function todo(): array
    {
        $guardados = Cache::remember('ajustes_documentos', 300, function () {
            try {
                return AjusteDocumento::pluck('valor', 'clave')->all();
            } catch (\Throwable) {
                return [];
            }
        });

        return array_merge(self::defaults(), array_filter($guardados, fn ($v) => filled($v)));
    }

    public static function c(string $nombre): string
    {
        return self::todo()['color_' . $nombre] ?? '#000000';
    }

    public static function guardar(array $datos): void
    {
        foreach (array_keys(self::defaults()) as $clave) {
            $valor = $datos[$clave] ?? null;
            if (is_array($valor)) {
                $valor = reset($valor) ?: null; // FileUpload devuelve un arreglo en algunas versiones
            }
            AjusteDocumento::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
        }
        Cache::forget('ajustes_documentos');
    }

    /** Logo como data URI (Dompdf no carga imágenes remotas). Solo PNG o JPG. */
    public static function logo(): string
    {
        $ruta = self::todo()['logo'] ?? null;
        $archivo = $ruta && Storage::disk('public')->exists($ruta)
            ? Storage::disk('public')->path($ruta)
            : collect([public_path('brand/logo-documentos.png'), public_path('brand/control-total-fondo-claro.png'), public_path('images/pdf/simbolo.png')])->first(fn ($p) => is_file($p));

        $mime = str_ends_with(strtolower($archivo), '.png') ? 'image/png' : 'image/jpeg';

        return "data:{$mime};base64," . base64_encode(file_get_contents($archivo));
    }
}
