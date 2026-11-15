<?php

namespace Database\Seeders;

use App\Models\PlantillaNotificacion;
use Illuminate\Database\Seeder;

/** Plantillas de correo. firstOrCreate: no pisa lo que ya se haya editado en el panel. */
class PlantillasCorreoSeeder extends Seeder
{
    public function run(): void
    {
        $base = [
            'push_titulo' => 'Control Total',
            'push_cuerpo' => 'Tienes un aviso nuevo',
            'correo_activo' => true,
            'push_activo' => false,
            'avisar_tecnico' => false,
            'roles_extra' => null,
            'activa' => true,
        ];

        $plantillas = [
            [
                'clave' => 'contacto_web_nuevo',
                'nombre' => 'Contacto nuevo desde la web',
                'descripcion' => 'Alguien llena el formulario de contacto del sitio (aviso interno).',
                'titulo' => 'Nuevo contacto web: {nombre}',
                'cuerpo' => "Llegó un mensaje desde el formulario de la web.\n\n{nombre} · Tel. {telefono} · {correo}\n\nCiudad: {ciudad}. Servicio: {servicio}.\n\nMensaje: {mensaje}",
                'push_titulo' => 'Nuevo contacto web',
                'push_cuerpo' => 'Alguien escribió desde el sitio',
                'push_activo' => true,
                'roles_extra' => ['administrador', 'coordinador'],
            ],
            [
                'clave' => 'contacto_recibido',
                'nombre' => 'Acuse de contacto web',
                'descripcion' => 'Se envía al visitante que dejó su correo. No incluye su mensaje a propósito.',
                'titulo' => 'Recibimos tu mensaje',
                'cuerpo' => "Gracias por escribirnos. Recibimos tu mensaje y un asesor de Control Total te contactará pronto.\n\nSi es urgente, llámanos o escríbenos por WhatsApp al +504 8958 6548.",
            ],
            [
                'clave' => 'cotizacion_enviada',
                'nombre' => 'Cotización enviada al cliente',
                'descripcion' => 'Se envía a los contactos del cliente cuando la cotización pasa a enviada. Incluye el enlace para revisarla y aceptarla.',
                'titulo' => 'Cotización {cotizacion} de Control Total',
                'cuerpo' => "Le compartimos la cotización {cotizacion} para {cliente}, por un total de {total}.\n\nEs válida hasta el {valida_hasta}. Puede revisarla, aceptarla o rechazarla desde el botón de abajo.\n\nSi tiene dudas, responda este correo o escríbanos por WhatsApp al +504 8958 6548.",
            ],
        ];

        foreach ($plantillas as $p) {
            PlantillaNotificacion::firstOrCreate(['clave' => $p['clave']], $p + $base);
        }
    }
}
