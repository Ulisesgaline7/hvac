<?php

namespace App\Support;

/** Páginas de arranque: al crear una página se puede partir de alguna de estas. */
final class PlantillasWeb
{
    public static function opciones(): array
    {
        return [
            'texto' => 'Página de texto',
            'nosotros' => 'Nosotros',
            'servicio' => 'Página de un servicio',
            'contacto' => 'Contacto y ubicación',
            'galeria' => 'Galería de trabajos',
            'inicio' => 'Portada completa (como la actual)',
            'inicio_aircon' => 'Portada AirCon (carrusel, nosotros, razones, servicios, contacto)',
            'nosotros_completo' => 'Nosotros completa (presentación, razones y características)',
            'servicios_lista' => 'Servicios (lista, proceso y llamada a la acción)',
            'servicio_detalle' => 'Página de un servicio (detalle, pasos, preguntas y contacto)',
            'preguntas' => 'Preguntas frecuentes',
            'contacto_completo' => 'Contacto completo (formulario, mapa y ciudades)',
        ];
    }

    /**
     * Las páginas internas con las que arranca el sitio multipágina:
     * [título, dirección, plantilla, descripción para Google].
     */
    public static function paginasIniciales(): array
    {
        return [
            ['Nosotros', 'nosotros', 'nosotros_completo', 'Conoce a Control Total: quiénes somos, cómo trabajamos y por qué llevamos el control de cada equipo y cada servicio.'],
            ['Servicios', 'servicios', 'servicios_lista', 'Instalación, mantenimiento y reparación de aire acondicionado, refrigeración y ventilación para hogares, negocios e industria.'],
            ['Preguntas frecuentes', 'preguntas-frecuentes', 'preguntas', 'Respuestas a las dudas más comunes sobre cotizaciones, seguimiento de trabajos, garantía y mantenimiento.'],
            ['Contacto', 'contactanos', 'contacto_completo', 'Escríbenos para pedir tu cotización. Te respondemos lo antes posible.'],
        ];
    }

    public static function bloques(?string $clave): array
    {
        return match ($clave) {
            'texto' => [
                self::b('texto', ['titulo' => 'Título de la sección', 'contenido' => '<p>Escribe aquí el contenido de la página.</p>']),
            ],
            'nosotros' => [
                self::b('hero', ['titulo' => 'Quiénes somos', 'texto' => 'Una frase que resuma a tu empresa y lo que la hace diferente.', 'boton1_texto' => 'Pedir cotización', 'boton1_url' => '/#contacto']),
                self::b('imagen_texto', ['titulo' => 'Nuestra historia', 'contenido' => '<p>Cuenta cómo nació la empresa y qué la mueve.</p>', 'posicion' => 'derecha', 'fondo' => 'blanco']),
                self::b('caracteristicas', ['titulo' => 'Nuestros valores', 'intro' => 'Lo que nos guía en cada trabajo.', 'fondo' => 'banda', 'items' => [
                    ['titulo' => 'Compromiso', 'texto' => 'Describe este valor en una frase.'],
                    ['titulo' => 'Calidad', 'texto' => 'Describe este valor en una frase.'],
                    ['titulo' => 'Transparencia', 'texto' => 'Describe este valor en una frase.'],
                ]]),
                self::b('cta', ['titulo' => '¿Hablamos?', 'texto' => 'Cuéntanos qué necesitas y te respondemos lo antes posible.', 'boton_texto' => 'Pedir cotización', 'boton_url' => '/#contacto']),
            ],
            'servicio' => [
                self::b('hero', ['titulo' => 'Nombre del servicio', 'texto' => 'Resume en una frase qué incluye este servicio.', 'boton1_texto' => 'Pedir cotización', 'boton1_url' => '#contacto']),
                self::b('imagen_texto', ['titulo' => 'Qué incluye', 'contenido' => '<p>Describe el servicio, para quién es y qué resultado obtiene el cliente.</p>', 'posicion' => 'izquierda']),
                self::b('pasos', ['titulo' => 'Cómo lo hacemos', 'intro' => 'Un proceso claro de principio a fin.', 'fondo' => 'blanco', 'items' => [
                    ['titulo' => 'Visita', 'texto' => 'Revisamos el equipo o el espacio.'],
                    ['titulo' => 'Cotización', 'texto' => 'Recibes el precio claro, antes de empezar.'],
                    ['titulo' => 'Trabajo', 'texto' => 'Un técnico realiza el servicio.'],
                    ['titulo' => 'Garantía', 'texto' => 'Queda registrado y con garantía.'],
                ]]),
                self::b('faq', ['titulo' => 'Preguntas frecuentes', 'items' => [
                    ['pregunta' => '¿Cuánto tarda?', 'respuesta' => 'Escribe aquí la respuesta.'],
                    ['pregunta' => '¿Incluye garantía?', 'respuesta' => 'Escribe aquí la respuesta.'],
                    ['pregunta' => '¿Cómo se paga?', 'respuesta' => 'Escribe aquí la respuesta.'],
                ]]),
                self::b('contacto', ['ancla' => 'contacto', 'titulo' => 'Pide tu cotización', 'intro' => 'Cuéntanos qué necesitas y te respondemos lo antes posible.', 'fondo' => 'blanco']),
            ],
            'contacto' => [
                self::b('texto', ['titulo' => 'Contáctanos', 'contenido' => '<p>Estamos para ayudarte. Escríbenos o llámanos.</p>']),
                self::b('contacto', ['ancla' => 'contacto', 'titulo' => 'Escríbenos', 'intro' => 'Te respondemos lo antes posible.', 'fondo' => 'blanco']),
                self::b('incrustado', ['titulo' => 'Dónde estamos', 'codigo' => '']),
                self::b('ciudades', ['titulo' => 'Dónde atendemos', 'intro' => 'Estamos cerca de ti.']),
            ],
            'galeria' => [
                self::b('texto', ['titulo' => 'Nuestros trabajos', 'contenido' => '<p>Una muestra de lo que hemos instalado y reparado.</p>']),
                self::b('galeria', ['titulo' => '', 'columnas' => '3', 'imagenes' => []]),
                self::b('cta', ['titulo' => '¿Quieres un trabajo así?', 'texto' => 'Pide tu cotización sin compromiso.', 'boton_texto' => 'Pedir cotización', 'boton_url' => '/#contacto']),
            ],
            'inicio' => self::inicio(null, null),
            'inicio_aircon' => self::inicioAircon(),
            'nosotros_completo' => self::nosotrosCompleto(),
            'servicios_lista' => self::serviciosLista(),
            'servicio_detalle' => self::servicioDetalle(),
            'preguntas' => self::preguntas(),
            'contacto_completo' => self::contactoCompleto(),
            default => [],
        };
    }

    /** La portada actual del sitio, dividida en secciones. */
    public static function inicio(?string $heroTitulo, ?string $heroTexto): array
    {
        return [
            self::b('hero', [
                'titulo' => $heroTitulo ?: 'Frío y clima bajo control total.',
                'texto' => $heroTexto ?: 'Instalamos, mantenemos y reparamos aire acondicionado, refrigeración y ventilación. Cada trabajo queda registrado y tú puedes darle seguimiento en línea.',
                'boton1_texto' => 'Pedir cotización', 'boton1_url' => '#contacto',
                'boton2_texto' => 'Ver servicios', 'boton2_url' => '#servicios',
            ]),
            self::b('servicios', [
                'ancla' => 'servicios', 'fondo' => 'claro', 'titulo' => 'Servicios',
                'intro' => 'Para hogares, negocios e industria. Un solo equipo se encarga desde la instalación hasta el mantenimiento.',
            ]),
            self::b('pasos', [
                'ancla' => 'proceso', 'fondo' => 'blanco', 'titulo' => 'Cómo trabajamos',
                'intro' => 'Un proceso claro, en el que siempre sabes en qué paso está tu trabajo.',
                'items' => [
                    ['titulo' => 'Visita y diagnóstico', 'texto' => 'Revisamos tu equipo o tu espacio y te explicamos qué necesitas.'],
                    ['titulo' => 'Cotización clara', 'texto' => 'Recibes la cotización por enlace y la aceptas en línea, con firma digital.'],
                    ['titulo' => 'Trabajo en sitio', 'texto' => 'Un técnico identificado realiza el servicio y registra el equipo con su código QR.'],
                    ['titulo' => 'Informe y garantía', 'texto' => 'Te entregamos un informe con código de verificación, y tu garantía queda registrada.'],
                ],
            ]),
            self::b('caracteristicas', [
                'ancla' => 'control', 'fondo' => 'banda', 'titulo' => 'Por qué Control Total',
                'intro' => 'El nombre no es casualidad: llevamos el control de cada equipo y cada servicio, y tú también puedes verlo.',
                'items' => [
                    ['titulo' => 'Cada equipo, con su historial', 'texto' => 'Un código QR en cada equipo permite ver su ficha, sus servicios y sus mantenimientos.'],
                    ['titulo' => 'Cotizaciones que se aceptan en línea', 'texto' => 'Sin papeles ni idas y vueltas: aceptas o rechazas desde tu teléfono.'],
                    ['titulo' => 'Informes verificables', 'texto' => 'Cada informe lleva un código con el que se puede comprobar que es auténtico.'],
                    ['titulo' => 'Control del refrigerante', 'texto' => 'Llevamos la bitácora de cada cilindro y de cada carga usada en tus equipos.'],
                    ['titulo' => 'Portal para clientes', 'texto' => 'Consulta tus cotizaciones, órdenes de trabajo, facturas y recibos cuando quieras.'],
                    ['titulo' => 'Técnicos siempre conectados', 'texto' => 'Nuestros técnicos usan una app que sigue funcionando aunque no haya internet.'],
                ],
            ]),
            self::b('ciudades', ['ancla' => 'cobertura', 'fondo' => 'claro', 'titulo' => 'Dónde atendemos', 'intro' => 'Estamos cerca de ti.']),
            self::b('contacto', [
                'ancla' => 'contacto', 'fondo' => 'blanco', 'titulo' => 'Pide tu cotización',
                'intro' => 'Cuéntanos qué necesitas y te respondemos lo antes posible.',
            ]),
        ];
    }

    /** Portada pensada para el tema AirCon. */
    public static function inicioAircon(): array
    {
        return [
            self::b('carrusel', ['slides' => [
                ['titulo' => 'Tu climatización en manos expertas', 'texto' => 'Instalamos, mantenemos y reparamos aire acondicionado, refrigeración y ventilación, con seguimiento en línea de cada trabajo.', 'boton_texto' => 'Pedir cotización', 'boton_url' => '#contacto'],
                ['titulo' => 'Mantenimiento que evita fallas', 'texto' => 'Visitas programadas para que tus equipos no se detengan y consuman menos energía.', 'boton_texto' => 'Ver servicios', 'boton_url' => '#servicios'],
            ]]),
            self::b('acerca', [
                'ancla' => 'nosotros', 'fondo' => 'claro',
                'titulo' => 'Bienvenido a tu centro de servicio de climatización y refrigeración',
                'destacados' => [
                    ['icono' => '07', 'texto' => 'Técnicos expertos'],
                    ['icono' => '09', 'texto' => 'Servicio de calidad'],
                ],
                'parrafo' => '<p>Cuéntale a tus clientes quién eres: cuánto tiempo llevas trabajando, qué equipos atiendes y qué te hace diferente. Cada trabajo queda registrado y puedes darle seguimiento en línea.</p>',
                'mostrar_contacto' => true,
            ]),
            self::b('razones', [
                'ancla' => 'control', 'fondo' => 'claro',
                'titulo' => 'Por qué elegir Control Total',
                'parrafo' => 'Llevamos el control de cada equipo y cada servicio, y tú también puedes verlo.',
                'items' => [
                    ['icono' => '08', 'titulo' => 'Centro de servicio de confianza', 'texto' => 'Cada equipo tiene su ficha, su historial y su código QR.'],
                    ['icono' => '10', 'titulo' => 'Precios claros', 'texto' => 'Recibes la cotización por enlace y la aceptas en línea antes de empezar.'],
                    ['icono' => '06', 'titulo' => 'Soporte cuando lo necesitas', 'texto' => 'Atención de emergencias y garantía registrada en cada trabajo.'],
                ],
            ]),
            self::b('servicios', [
                'ancla' => 'servicios', 'fondo' => 'claro',
                'titulo' => 'Ofrecemos servicios profesionales de climatización y refrigeración',
                'mostrar_detalle' => true,
            ]),
            self::b('pasos', [
                'ancla' => 'proceso', 'fondo' => 'banda', 'titulo' => 'Cómo trabajamos',
                'intro' => 'Un proceso claro, en el que siempre sabes en qué paso está tu trabajo.',
                'items' => [
                    ['titulo' => 'Visita y diagnóstico', 'texto' => 'Revisamos tu equipo o tu espacio y te explicamos qué necesitas.'],
                    ['titulo' => 'Cotización clara', 'texto' => 'Recibes la cotización por enlace y la aceptas en línea, con firma digital.'],
                    ['titulo' => 'Trabajo en sitio', 'texto' => 'Un técnico identificado realiza el servicio y registra el equipo con su código QR.'],
                    ['titulo' => 'Informe y garantía', 'texto' => 'Te entregamos un informe con código de verificación, y tu garantía queda registrada.'],
                ],
            ]),
            self::b('contacto', [
                'ancla' => 'contacto', 'fondo' => 'claro',
                'titulo' => 'Si tienes alguna consulta, contáctanos',
                'intro' => 'Cuéntanos qué necesitas y te respondemos lo antes posible.',
                'mapa' => '',
            ]),
        ];
    }

    // ------------------------------------------------- páginas internas (multipágina)
    // Estas plantillas no llevan portada propia: la página muestra su título arriba
    // («Mostrar el título de la página»), así que empiezan directo con el contenido.

    public static function nosotrosCompleto(): array
    {
        return [
            self::b('acerca', [
                'ancla' => 'nosotros', 'fondo' => 'claro',
                'titulo' => 'Tu aliado en climatización y refrigeración',
                'destacados' => [
                    ['icono' => '07', 'texto' => 'Técnicos expertos'],
                    ['icono' => '09', 'texto' => 'Servicio de calidad'],
                ],
                'parrafo' => '<p>Instalamos, mantenemos y reparamos aire acondicionado, refrigeración y ventilación para hogares, negocios e industria.</p><p>Cada equipo queda registrado con su historial y cada trabajo tiene seguimiento en línea, para que siempre sepas qué se hizo y en qué paso va.</p>',
                'mostrar_contacto' => true,
            ]),
            self::b('razones', [
                'ancla' => 'control', 'fondo' => 'claro',
                'titulo' => 'Por qué elegirnos',
                'parrafo' => 'Llevamos el control de cada equipo y cada servicio, y tú también puedes verlo.',
                'items' => [
                    ['icono' => '08', 'titulo' => 'Centro de servicio de confianza', 'texto' => 'Cada equipo tiene su ficha, su historial y su código QR.'],
                    ['icono' => '10', 'titulo' => 'Precios claros', 'texto' => 'Recibes la cotización por enlace y la aceptas en línea antes de empezar.'],
                    ['icono' => '06', 'titulo' => 'Soporte cuando lo necesitas', 'texto' => 'Garantía registrada en cada trabajo y atención por los canales que ves en esta página.'],
                ],
            ]),
            self::b('caracteristicas', [
                'fondo' => 'banda', 'titulo' => 'Lo que nos distingue',
                'intro' => 'Tecnología al servicio de un trabajo bien hecho.',
                'items' => self::itemsControl(),
            ]),
            self::b('cta', [
                'titulo' => '¿Hablamos?', 'texto' => 'Cuéntanos qué necesitas y te respondemos lo antes posible.',
                'boton_texto' => 'Pedir cotización', 'boton_url' => '/contactanos',
            ]),
        ];
    }

    public static function serviciosLista(): array
    {
        return [
            self::b('servicios', [
                'ancla' => 'servicios', 'fondo' => 'claro',
                'titulo' => 'Servicios profesionales de climatización y refrigeración',
                'intro' => 'Para hogares, negocios e industria. Un solo equipo se encarga desde la instalación hasta el mantenimiento.',
                'mostrar_detalle' => true,
            ]),
            self::b('pasos', [
                'ancla' => 'proceso', 'fondo' => 'banda', 'titulo' => 'Cómo trabajamos',
                'intro' => 'Un proceso claro, en el que siempre sabes en qué paso está tu trabajo.',
                'items' => self::itemsProceso(),
            ]),
            self::b('cta', [
                'titulo' => '¿Necesitas alguno de estos servicios?',
                'texto' => 'Cuéntanos qué necesitas y te enviamos una cotización clara.',
                'boton_texto' => 'Pedir cotización', 'boton_url' => '/contactanos',
            ]),
        ];
    }

    /** Para un servicio en particular: copia, cambia los textos y publícala con su propia dirección. */
    public static function servicioDetalle(): array
    {
        return [
            self::b('imagen_texto', [
                'fondo' => 'claro', 'posicion' => 'izquierda', 'titulo' => 'Qué incluye',
                'contenido' => '<p>Describe el servicio, para quién es y qué resultado obtiene el cliente.</p><ul><li>Primer beneficio o actividad incluida</li><li>Segundo beneficio o actividad incluida</li><li>Tercer beneficio o actividad incluida</li></ul>',
            ]),
            self::b('pasos', [
                'fondo' => 'banda', 'titulo' => 'Cómo lo hacemos',
                'intro' => 'Un proceso claro de principio a fin.',
                'items' => self::itemsProceso(),
            ]),
            self::b('faq', [
                'fondo' => 'claro', 'titulo' => 'Preguntas sobre este servicio',
                'items' => [
                    ['pregunta' => '¿Cuánto tarda?', 'respuesta' => 'Escribe aquí la respuesta.'],
                    ['pregunta' => '¿Incluye garantía?', 'respuesta' => 'Escribe aquí la respuesta.'],
                    ['pregunta' => '¿Cómo se paga?', 'respuesta' => 'Escribe aquí la respuesta.'],
                ],
            ]),
            self::b('contacto', [
                'ancla' => 'contacto', 'fondo' => 'banda', 'titulo' => 'Pide tu cotización',
                'intro' => 'Cuéntanos qué necesitas y te respondemos lo antes posible.',
            ]),
        ];
    }

    public static function preguntas(): array
    {
        return [
            self::b('faq', [
                'fondo' => 'claro', 'titulo' => 'Resolvemos tus dudas',
                'items' => [
                    ['pregunta' => '¿Cómo pido una cotización?', 'respuesta' => 'Escríbenos desde la página de contacto y cuéntanos qué necesitas. Te enviamos la cotización por enlace y la puedes aceptar en línea, con firma digital.'],
                    ['pregunta' => '¿Puedo ver cómo va mi trabajo?', 'respuesta' => 'Sí. En el portal para clientes consultas tus cotizaciones, órdenes de trabajo, facturas y recibos cuando quieras.'],
                    ['pregunta' => '¿Qué es el código QR de mi equipo?', 'respuesta' => 'Es una etiqueta que dejamos en cada equipo. Al escanearla se ve su ficha, los servicios realizados y sus mantenimientos.'],
                    ['pregunta' => '¿Los trabajos tienen garantía?', 'respuesta' => 'La garantía de cada trabajo queda registrada en su informe, y te la indicamos en la cotización.'],
                    ['pregunta' => '¿Cada cuánto necesita mantenimiento mi equipo?', 'respuesta' => 'Depende del tipo de equipo y de cuánto se use. Después del diagnóstico te recomendamos la frecuencia adecuada.'],
                    ['pregunta' => '¿En qué zonas trabajan?', 'respuesta' => 'Consulta «Dónde atendemos» en la página de contacto. Si tu zona no aparece, escríbenos y te confirmamos.'],
                ],
            ]),
            self::b('cta', [
                'titulo' => '¿Te quedó alguna duda?', 'texto' => 'Escríbenos y te respondemos lo antes posible.',
                'boton_texto' => 'Contáctanos', 'boton_url' => '/contactanos',
            ]),
        ];
    }

    public static function contactoCompleto(): array
    {
        return [
            self::b('contacto', [
                'ancla' => 'contacto', 'fondo' => 'claro',
                'titulo' => 'Si tienes alguna consulta, contáctanos',
                'intro' => 'Cuéntanos qué necesitas y te respondemos lo antes posible.',
                'mapa' => '',
            ]),
            self::b('ciudades', [
                'ancla' => 'cobertura', 'fondo' => 'banda',
                'titulo' => 'Dónde atendemos', 'intro' => 'Estamos cerca de ti.',
            ]),
        ];
    }

    private static function itemsProceso(): array
    {
        return [
            ['titulo' => 'Visita y diagnóstico', 'texto' => 'Revisamos tu equipo o tu espacio y te explicamos qué necesitas.'],
            ['titulo' => 'Cotización clara', 'texto' => 'Recibes la cotización por enlace y la aceptas en línea, con firma digital.'],
            ['titulo' => 'Trabajo en sitio', 'texto' => 'Un técnico identificado realiza el servicio y registra el equipo con su código QR.'],
            ['titulo' => 'Informe y garantía', 'texto' => 'Te entregamos un informe con código de verificación, y tu garantía queda registrada.'],
        ];
    }

    private static function itemsControl(): array
    {
        return [
            ['titulo' => 'Cada equipo, con su historial', 'texto' => 'Un código QR en cada equipo permite ver su ficha, sus servicios y sus mantenimientos.'],
            ['titulo' => 'Cotizaciones que se aceptan en línea', 'texto' => 'Sin papeles ni idas y vueltas: aceptas o rechazas desde tu teléfono.'],
            ['titulo' => 'Informes verificables', 'texto' => 'Cada informe lleva un código con el que se puede comprobar que es auténtico.'],
            ['titulo' => 'Control del refrigerante', 'texto' => 'Llevamos la bitácora de cada cilindro y de cada carga usada en tus equipos.'],
            ['titulo' => 'Portal para clientes', 'texto' => 'Consulta tus cotizaciones, órdenes de trabajo, facturas y recibos cuando quieras.'],
            ['titulo' => 'Técnicos siempre conectados', 'texto' => 'Nuestros técnicos usan una app que sigue funcionando aunque no haya internet.'],
        ];
    }

    /** Bloques con claves únicas, como los espera el constructor de Filament. */
    public static function conIds(?string $clave): array
    {
        $salida = [];
        foreach (self::bloques($clave) as $bloque) {
            foreach ($bloque['data'] as $campo => $valor) {
                if (is_array($valor) && $valor !== [] && array_is_list($valor) && is_array($valor[0])) {
                    $bloque['data'][$campo] = self::indexar($valor);
                }
            }
            $salida[self::uuid()] = $bloque;
        }

        return $salida;
    }

    private static function indexar(array $lista): array
    {
        $r = [];
        foreach ($lista as $item) {
            $r[self::uuid()] = $item;
        }

        return $r;
    }

    private static function b(string $tipo, array $data): array
    {
        return ['type' => $tipo, 'data' => $data];
    }

    private static function uuid(): string
    {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }
}
