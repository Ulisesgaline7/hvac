<?php

namespace App\Support;

/** Servicios del Tarifario rev. 2 (14/10/2026). Precios sin ISV, en lempiras. */
final class TarifarioCatalogo
{
    // [codigo, categoria, nombre, detalle, precio, precio_hasta, cuenta_equipo]
    private const FILAS = [
        ['T-01', 'preventivo', 'Mantenimiento preventivo 12,000 BTU', 'Mini split / pared', 800, null, true],
        ['T-02', 'preventivo', 'Mantenimiento preventivo 18,000 BTU', 'Mini split / pared', 900, null, true],
        ['T-03', 'preventivo', 'Mantenimiento preventivo 24,000 BTU', 'Mini split / pared', 1050, null, true],
        ['T-04', 'preventivo', 'Mantenimiento preventivo 36,000 BTU (3 TR)', 'Mini split, piso-techo, cassette o ducto', 1300, null, true],
        ['T-05', 'preventivo', 'Mantenimiento preventivo 48,000 BTU (4 TR)', 'Mini split, piso-techo, cassette o ducto', 1600, null, true],
        ['T-06', 'preventivo', 'Mantenimiento preventivo 60,000 BTU (5 TR)', 'Mini split, piso-techo, cassette o ducto', 1800, null, true],
        ['T-07', 'preventivo', 'Mantenimiento de unidad paquete / rooftop 5 a 10 TR', 'Por unidad', 2500, null, true],
        ['T-08', 'preventivo', 'Mantenimiento de manejadora (UMA)', 'Por unidad', 1500, null, true],
        ['T-09', 'preventivo', 'Mantenimiento de chiller', 'Desde, según capacidad', 4500, null, true],
        ['T-10', 'instalacion', 'Instalación mini split 12,000 BTU', 'Mano de obra, hasta 3 m de tubería', 2300, null, true],
        ['T-11', 'instalacion', 'Instalación mini split 18,000 a 24,000 BTU', 'Mano de obra', 3100, null, true],
        ['T-12', 'instalacion', 'Instalación split 36,000 BTU o más', 'Mano de obra', 5200, null, true],
        ['T-13', 'instalacion', 'Instalación piso-techo / cassette / ducto 36,000 a 60,000 BTU', 'Mano de obra', 6500, 9500, true],
        ['T-14', 'instalacion', 'Tubería de cobre adicional', 'Por metro', 325, null, false],
        ['T-15', 'instalacion', 'Soporte de condensadora', 'Par de bases', 450, null, false],
        ['T-16', 'instalacion', 'Desinstalación de equipo', 'Por unidad', 900, null, true],
        ['T-17', 'instalacion', 'Circuito eléctrico dedicado', 'Por metro', 180, null, false],
        ['T-18', 'refrigeracion_comercial', 'Mantenimiento de refrigerador / congelador / vitrina', 'Por unidad', 600, null, true],
        ['T-19', 'refrigeracion_comercial', 'Mantenimiento de máquina de hielo', 'Por unidad', 900, null, true],
        ['T-20', 'refrigeracion_comercial', 'Mantenimiento de cuarto frío / cámara', 'Por cámara', 1800, null, true],
        ['T-21', 'refrigeracion_comercial', 'Mantenimiento de cámara de congelación', 'Por cámara', 2300, null, true],
        ['T-22', 'correctivo', 'Visita técnica y diagnóstico', 'Se descuenta si se aprueba la reparación', 500, 800, false],
        ['T-23', 'correctivo', 'Detección de fuga y soldadura', 'Mano de obra', 1200, 2500, false],
        ['T-24', 'correctivo', 'Vacío y deshidratación de sistema', 'Mano de obra', 800, null, false],
        ['T-25', 'correctivo', 'Cambio de capacitor o contactor', 'Mano de obra', 600, 1200, false],
        ['T-26', 'correctivo', 'Cambio de motor ventilador', 'Mano de obra', 900, 1800, false],
        ['T-27', 'correctivo', 'Cambio de tarjeta electrónica o sensores', 'Mano de obra', 800, 1500, false],
        ['T-28', 'correctivo', 'Cambio de compresor, aire acondicionado', 'Mano de obra', 4500, 12000, false],
        ['T-29', 'correctivo', 'Cambio de compresor, equipo comercial', 'Mano de obra', 3500, 9000, false],
        ['T-30', 'correctivo', 'Cambio de evaporador / condensador', 'Mano de obra', 2500, 6500, false],
        ['T-31', 'repuestos_gas', 'Carga de gas R410A / R22 / R134a', 'Por libra, incluye gas', 350, 450, false],
        ['T-32', 'administracion', 'Técnico por administración', 'Por hora', 250, null, false],
        ['T-33', 'administracion', 'Ayudante por administración', 'Por hora', 130, null, false],
        ['T-34', 'administracion', 'Supervisor / ingeniero por administración', 'Por hora', 350, null, false],
    ];

    public static function filas(): array
    {
        return array_map(fn ($f) => [
            'codigo' => $f[0], 'categoria' => $f[1], 'nombre' => $f[2], 'detalle' => $f[3],
            'precio' => $f[4], 'precio_hasta' => $f[5], 'cuenta_equipo' => $f[6], 'activo' => true,
        ], self::FILAS);
    }
}
