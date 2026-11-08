<?php

return [
    'listas' => [
        'seguridad' => [
            'nombre' => 'Seguridad y bloqueo (LOTO)',
            'tareas' => [
                'Avisar al cliente y delimitar el área de trabajo',
                'Apagar la fuente de energía (breaker o desconectador)',
                'Bloquear y etiquetar el apagado (candado y tarjeta)',
                'Verificar ausencia de voltaje con multímetro',
                'Descargar capacitores',
            ],
        ],
        'preventivo_split' => [
            'nombre' => 'Preventivo · Mini split / Paquete',
            'tareas' => [
                'Apagar la fuente de energía y etiquetar el apagado',
                'Desmontar cubiertas y filtros',
                'Limpiar filtros',
                'Limpiar serpentín evaporador',
                'Limpiar serpentín condensador',
                'Limpiar bandeja y drenaje de condensados',
                'Revisar y apretar conexiones eléctricas',
                'Revisar ventiladores y rodamientos',
                'Medir amperaje y voltaje del compresor',
                'Medir presiones y temperaturas de retorno y suministro',
                'Montar cubiertas y retirar bloqueo',
                'Encender y probar el equipo',
                'Limpiar el área de trabajo',
            ],
        ],
        'preventivo_chiller' => [
            'nombre' => 'Preventivo · Chiller / Rooftop',
            'tareas' => [
                'Apagar la fuente de energía y etiquetar el apagado',
                'Revisar nivel y presión de refrigerante',
                'Limpiar serpentines del condensador',
                'Revisar contactores y breakers',
                'Revisar bombas y flujo de agua',
                'Revisar correas y poleas',
                'Lubricar motores y rodamientos',
                'Verificar alarmas y registros del controlador',
                'Retirar bloqueo, encender y probar',
            ],
        ],
        'instalacion' => [
            'nombre' => 'Instalación',
            'tareas' => [
                'Verificar sitio, soporte y distancias',
                'Instalar unidad interior',
                'Instalar unidad exterior',
                'Tender tubería de cobre y aislarla',
                'Tender cableado eléctrico y protección',
                'Hacer prueba de hermeticidad con nitrógeno',
                'Hacer vacío',
                'Liberar o cargar refrigerante',
                'Instalar drenaje',
                'Hacer prueba final y registrar mediciones',
                'Capacitar al cliente y entregar documentos',
            ],
        ],
        'cuarto_frio' => [
            'nombre' => 'Preventivo · Cuarto frío / Refrigeración',
            'tareas' => [
                'Apagar la fuente de energía y etiquetar el apagado',
                'Revisar sellos y puertas',
                'Limpiar evaporador y condensador',
                'Revisar ciclo de deshielo y resistencias',
                'Revisar nivel de aceite y refrigerante',
                'Revisar termostato y sensores',
                'Retirar bloqueo, encender y probar temperatura',
            ],
        ],
        'correctivo' => [
            'nombre' => 'Correctivo · Diagnóstico',
            'tareas' => [
                'Apagar la fuente de energía y etiquetar el apagado',
                'Describir la falla reportada',
                'Inspección visual y eléctrica',
                'Medir presiones, voltajes y amperajes',
                'Identificar la causa de la falla',
                'Desmontar y reemplazar el componente dañado',
                'Retirar bloqueo, encender y probar',
                'Registrar repuestos usados',
            ],
        ],
    ],
];
