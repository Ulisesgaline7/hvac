<?php

namespace App\Support;

use Filament\Support\Colors\Color;

/**
 * Identidad de Control Total en un solo lugar: nombre, logos y paleta.
 * La usan los tres paneles (admin, portal y técnico); el sitio web toma los mismos colores de su CSS.
 * No consulta la base de datos a propósito: se usa al arrancar los paneles.
 */
final class Marca
{
    public const NOMBRE = 'Control Total';

    // Paleta (la misma de public/template/controltotal.css y de los logos).
    public const PRIMARIO = '#087BFF';
    public const SECUNDARIO = '#0D3B66';
    public const OSCURO = '#082A4A';
    public const CLARO = '#F4F8FC';
    public const ACENTO = '#00BFE0';

    public static function nombre(): string
    {
        return self::NOMBRE;
    }

    /** Escala de color de Filament para el color principal. */
    public static function primario(): array
    {
        if (method_exists(Color::class, 'hex')) {
            return Color::hex(self::PRIMARIO);
        }

        if (method_exists(Color::class, 'generateV3Palette')) {
            return Color::generateV3Palette(self::PRIMARIO);
        }

        return Color::Blue;
    }

    /** Logo para fondos claros (modo claro del panel). */
    public static function logoClaro(): string
    {
        return asset('brand/control-total-fondo-claro.png');
    }

    /** Logo para fondos oscuros (modo oscuro del panel). */
    public static function logoOscuro(): string
    {
        return asset('brand/control-total-fondo-oscuro.png');
    }

    public static function favicon(): string
    {
        return asset('brand/control-total-simbolo.svg');
    }

    public static function alturaLogo(): string
    {
        return '2.5rem';
    }
}
