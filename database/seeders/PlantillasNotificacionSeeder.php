<?php

namespace Database\Seeders;

use App\Models\PlantillaNotificacion;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/** No pisa plantillas existentes: lo que el admin edite se conserva. */
class PlantillasNotificacionSeeder extends Seeder
{
    public function run(): void
    {
        $internos = Role::whereIn('name', ['administrador', 'coordinador'])->pluck('name')->all();

        $defs = [
            [
                'clave' => 'pago_aprobado',
                'nombre' => 'Pago aprobado',
                'descripcion' => 'El admin aprueba una línea de pago del técnico.',
                'titulo' => 'Pago aprobado',
                'cuerpo' => "Se aprobó tu pago de {monto} por «{concepto}» en la OT {ot}.\n\nSe incluirá en tu próxima liquidación semanal.",
                'avisar_tecnico' => true,
                'roles_extra' => [],
            ],
            [
                'clave' => 'pago_sin_tarifa',
                'nombre' => 'Pago sin tarifa',
                'descripcion' => 'Al cerrar una OT, un concepto quedó sin tarifa y requiere revisión (aviso interno).',
                'titulo' => 'OT sin tarifa: {ot}',
                'cuerpo' => "La OT {ot} cerró con el concepto «{concepto}» sin tarifa asignada.\n\nRevisa el tarifario para generar el pago.",
                'avisar_tecnico' => false,
                'roles_extra' => $internos,
            ],
            [
                'clave' => 'retorno_registrado',
                'nombre' => 'Retorno por falla de ejecución',
                'descripcion' => 'Una OT correctiva se cierra como retorno de una OT anterior (sin pago).',
                'titulo' => 'Retorno registrado: {ot}',
                'cuerpo' => "La OT {ot} fue registrada como retorno por falla de ejecución (técnico: {tecnico}).\n\nEste servicio no genera pago; conviene revisar el caso.",
                'avisar_tecnico' => true,
                'roles_extra' => $internos,
            ],
            [
                'clave' => 'liquidacion_pagada',
                'nombre' => 'Liquidación pagada',
                'descripcion' => 'El admin marca la liquidación semanal como pagada.',
                'titulo' => 'Liquidación pagada {numero}',
                'cuerpo' => "Se pagó tu liquidación {numero} de la semana del {semana_inicio} al {semana_fin}.\n\nBruto: {bruto}. Retención: {retencion}. Neto pagado: {neto}.\n\nMétodo: {metodo}. Referencia: {referencia}. Fecha: {fecha_pago}.",
                'avisar_tecnico' => true,
                'roles_extra' => [],
            ],
            [
                'clave' => 'liquidacion_anulada',
                'nombre' => 'Liquidación anulada',
                'descripcion' => 'El admin anula una liquidación; sus pagos vuelven a quedar pendientes de liquidar.',
                'titulo' => 'Liquidación anulada {numero}',
                'cuerpo' => "La liquidación {numero} ({semana_inicio} al {semana_fin}) fue anulada.\n\nMotivo: {motivo}.\n\nTus pagos volverán a quedar pendientes de liquidar.",
                'avisar_tecnico' => true,
                'roles_extra' => [],
            ],
        ];

        foreach ($defs as $d) {
            PlantillaNotificacion::firstOrCreate(['clave' => $d['clave']], $d);
        }
    }
}
