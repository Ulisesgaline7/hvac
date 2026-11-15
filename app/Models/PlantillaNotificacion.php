<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantillaNotificacion extends Model
{
    protected $table = 'plantillas_notificacion';
    protected $guarded = [];

    protected $casts = [
        'correo_activo' => 'boolean',
        'push_activo' => 'boolean',
        'avisar_tecnico' => 'boolean',
        'activa' => 'boolean',
        'roles_extra' => 'array',
    ];

    /** Variables disponibles por evento (se muestran como ayuda al editar). */
    public const VARIABLES = [
        'pago_aprobado' => ['tecnico', 'ot', 'concepto', 'monto', 'rol'],
        'pago_sin_tarifa' => ['tecnico', 'ot', 'concepto'],
        'retorno_registrado' => ['tecnico', 'ot'],
        'liquidacion_pagada' => ['tecnico', 'numero', 'semana_inicio', 'semana_fin', 'bruto', 'retencion', 'neto', 'metodo', 'referencia', 'fecha_pago'],
        'liquidacion_anulada' => ['tecnico', 'numero', 'semana_inicio', 'semana_fin', 'neto', 'motivo'],
        'contacto_web_nuevo' => ['nombre', 'telefono', 'correo', 'ciudad', 'servicio', 'mensaje'],
        'contacto_recibido' => ['nombre'],
        'cotizacion_enviada' => ['nombre', 'cliente', 'cotizacion', 'total', 'desglose', 'valida_hasta'],
    ];

    /** Valores de ejemplo para el botón "Enviarme una prueba". */
    public const EJEMPLO = [
        'tecnico' => 'Técnico de prueba',
        'ot' => 'OT-0001',
        'concepto' => 'Mantenimiento mini split',
        'monto' => 'L 1,250.00',
        'rol' => 'lider',
        'numero' => 'LQ-0001',
        'semana_inicio' => '03/11/2026',
        'semana_fin' => '09/11/2026',
        'bruto' => 'L 5,000.00',
        'retencion' => 'L 500.00',
        'neto' => 'L 4,500.00',
        'metodo' => 'transferencia',
        'referencia' => 'REF-123',
        'fecha_pago' => '10/11/2026',
        'motivo' => 'Error de captura',
        'nombre' => 'María Pérez',
        'telefono' => '9999-9999',
        'correo' => 'maria@ejemplo.hn',
        'ciudad' => 'Tegucigalpa',
        'servicio' => 'Aire acondicionado',
        'mensaje' => 'Quiero una cotización para dos equipos.',
        'cliente' => 'Empresa de prueba',
        'cotizacion' => 'COT-0001',
        'total' => 'L 12,500.00',
        'valida_hasta' => '20/11/2026',
        'desglose' => "Servicios: L 800.00\n\nRecargos: L 240.00\n\nSubtotal: L 1,040.00\n\nISV (15 %): L 156.00\n\nTOTAL: L 1,196.00",
    ];
}
