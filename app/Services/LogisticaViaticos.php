<?php

namespace App\Services;

/**
 * Movilización y viáticos de la política detallada (propuesta comercial).
 * Clase pura: recibe los datos y las tarifas, no lee configuración ni base de datos.
 */
class LogisticaViaticos
{
    /**
     * @param  array{km?:mixed,tecnicos?:mixed,dias?:mixed,noches?:mixed,peajes?:mixed}  $datos
     * @param  array{km_tarifa?:mixed,alimentacion_dia?:mixed,hospedaje_noche?:mixed}  $tarifas
     */
    public function calcular(array $datos, array $tarifas): array
    {
        $km = max(0.0, (float) ($datos['km'] ?? 0));
        $tecnicos = max(0, (int) ($datos['tecnicos'] ?? 0));
        $dias = max(0, (int) ($datos['dias'] ?? 0));
        $noches = max(0, (int) ($datos['noches'] ?? 0));
        $peajes = max(0.0, (float) ($datos['peajes'] ?? 0));

        $tarifaKm = (float) ($tarifas['km_tarifa'] ?? 0);
        $tarifaAlimentacion = (float) ($tarifas['alimentacion_dia'] ?? 0);
        $tarifaHospedaje = (float) ($tarifas['hospedaje_noche'] ?? 0);

        $movilizacion = round($km * $tarifaKm, 2);
        $alimentacion = round($tecnicos * $dias * $tarifaAlimentacion, 2);
        $hospedaje = round($tecnicos * $noches * $tarifaHospedaje, 2);
        $peajes = round($peajes, 2);
        $viaticos = round($alimentacion + $hospedaje + $peajes, 2);

        return [
            'movilizacion' => $movilizacion,
            'alimentacion' => $alimentacion,
            'hospedaje' => $hospedaje,
            'peajes' => $peajes,
            'viaticos' => $viaticos,
            'total' => round($movilizacion + $viaticos, 2),
            // Respaldo de lo aplicado: la cotización se puede reproducir aunque cambien las tarifas después.
            'detalle' => [
                'km' => $km, 'tarifa_km' => $tarifaKm,
                'tecnicos' => $tecnicos, 'dias' => $dias, 'noches' => $noches,
                'tarifa_alimentacion' => $tarifaAlimentacion, 'tarifa_hospedaje' => $tarifaHospedaje,
                'movilizacion' => $movilizacion, 'alimentacion' => $alimentacion,
                'hospedaje' => $hospedaje, 'peajes' => $peajes,
            ],
        ];
    }

    /** Total anual de un plan: el subtotal por visita por el número de visitas, y el ISV sobre ese subtotal. */
    public static function anual(float $subtotalVisita, int $visitas, float $isv): array
    {
        $subtotal = round($subtotalVisita * max(1, $visitas), 2);
        $impuesto = round($subtotal * $isv, 2);

        return ['subtotal' => $subtotal, 'isv' => $impuesto, 'total' => round($subtotal + $impuesto, 2)];
    }
}
