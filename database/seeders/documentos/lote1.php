<?php

return [

'DOC-001' => <<<'HTML'
<h2>1. Alcance de la cotización</h2>
<p>Esta cotización describe los trabajos, equipos y materiales que Control Total Servicios Integrales S. de R.L. propone ejecutar. Lo que no aparezca detallado en ella no forma parte del alcance y, si se requiere, se cotiza por separado.</p>
<h2>2. Validez y precios</h2>
<ul>
<li>La cotización tiene una validez de 15 días calendario desde su fecha de emisión.</li>
<li>Los precios están expresados en Lempiras (L) e incluyen el Impuesto Sobre Ventas (ISV) cuando así se indique en el detalle.</li>
<li>Los precios de equipos y materiales pueden variar si cambia su costo de reposición después de vencida la validez.</li>
</ul>
<h2>3. Condiciones de pago</h2>
<ul>
<li>Instalaciones y proyectos: anticipo del 50 % para programar el trabajo y el saldo contra entrega y conformidad.</li>
<li>Mantenimientos y reparaciones: pago al finalizar el servicio, salvo que exista un contrato con otra condición.</li>
<li>Los pagos pueden hacerse por transferencia, depósito o efectivo, y se confirman con el comprobante correspondiente.</li>
</ul>
<h2>4. Trabajos adicionales</h2>
<p>Si durante la ejecución se detectan fallas o trabajos no incluidos, el técnico los informa y se ejecutan solo con la aprobación del cliente (ver Aprobación de Trabajo Adicional). Ningún trabajo adicional se cobra sin esa aprobación.</p>
<h2>5. Garantía</h2>
<p>Los trabajos tienen la garantía descrita en el Certificado de Garantía. Los equipos y repuestos nuevos tienen la garantía del fabricante o proveedor. La garantía no cubre daños por mal uso, variaciones de voltaje, manipulación por terceros ni falta de mantenimiento.</p>
<h2>6. Responsabilidades del cliente</h2>
<ul>
<li>Facilitar el acceso al sitio y a los equipos en la fecha acordada.</li>
<li>Contar con suministro eléctrico adecuado y puntos de conexión disponibles.</li>
<li>Informar sobre riesgos conocidos del lugar (alturas, áreas restringidas, instalaciones antiguas).</li>
</ul>
<h2>7. Aceptación</h2>
<p>La aceptación de esta cotización, por escrito o por mensaje confirmado, indica conformidad con el alcance, los precios y las condiciones descritas.</p>
HTML,

'DOC-002' => <<<'HTML'
<h2>1. Partes</h2>
<p>El presente contrato se celebra entre <strong>Control Total Servicios Integrales S. de R.L.</strong>, con domicilio en Tegucigalpa, M.D.C., Honduras (en adelante «el Proveedor»), y el cliente identificado en la ficha del contrato (en adelante «el Cliente»).</p>
<h2>2. Objeto</h2>
<p>El Proveedor prestará al Cliente el servicio de mantenimiento preventivo y correctivo de los equipos de aire acondicionado y refrigeración listados en el anexo de equipos, con la frecuencia y el alcance indicados en este contrato.</p>
<h2>3. Alcance del servicio</h2>
<ul>
<li>Visitas de mantenimiento preventivo con la frecuencia pactada.</li>
<li>Revisión, limpieza, mediciones eléctricas y de presión, y reporte de cada visita.</li>
<li>Atención de fallas según el nivel de servicio acordado.</li>
<li>Repuestos y refrigerante: no incluidos, salvo pacto escrito. Se cotizan y requieren aprobación previa.</li>
</ul>
<h2>4. Obligaciones del Proveedor</h2>
<ul>
<li>Enviar personal capacitado, identificado y con equipo de protección.</li>
<li>Entregar un reporte de servicio firmado tras cada visita.</li>
<li>Manejar el refrigerante conforme a la Política de Refrigerantes y registrarlo en la bitácora.</li>
<li>Avisar de inmediato cualquier riesgo o falla que detecte.</li>
</ul>
<h2>5. Obligaciones del Cliente</h2>
<ul>
<li>Permitir el acceso a los equipos en las fechas programadas.</li>
<li>Pagar la cuota pactada en las fechas acordadas.</li>
<li>Avisar oportunamente de fallas, cambios de ubicación o modificaciones de los equipos.</li>
</ul>
<h2>5. Precio y forma de pago</h2>
<p>La cuota, su periodicidad y la forma de pago se establecen en la ficha del contrato. Los pagos atrasados pueden suspender las visitas programadas hasta regularizarse.</p>
<h2>6. Vigencia y terminación</h2>
<p>El contrato tiene la vigencia indicada en la ficha y se renueva por periodos iguales si ninguna de las partes avisa por escrito su deseo de terminarlo con al menos 30 días de anticipación.</p>
<h2>7. Limitación de responsabilidad</h2>
<p>El Proveedor responde por la correcta ejecución de sus trabajos. No responde por daños derivados de fallas preexistentes no informadas, del estado de las instalaciones del Cliente o de intervenciones de terceros en los equipos.</p>
<h2>8. Confidencialidad y datos</h2>
<p>Ambas partes se comprometen a tratar con reserva la información intercambiada. Los datos personales se tratan según el Aviso de Privacidad.</p>
<h2>9. Ley aplicable</h2>
<p>Este contrato se rige por las leyes de la República de Honduras. Las diferencias se buscarán resolver primero de común acuerdo.</p>
<p><em>Texto base: debe ser revisado por un abogado antes de su uso formal.</em></p>
HTML,

'DOC-003' => <<<'HTML'
<h2>1. Propósito</h2>
<p>La Orden de Trabajo (OT) es el registro único de un servicio: qué se va a hacer, dónde, quién lo hace y bajo qué condiciones. Ningún trabajo inicia sin una OT.</p>
<h2>2. Datos de la orden</h2>
<table>
<tr><th>Campo</th><th>Descripción</th></tr>
<tr><td>Número de OT</td><td>Generado por el sistema.</td></tr>
<tr><td>Cliente y sitio</td><td>Nombre, dirección y contacto en sitio.</td></tr>
<tr><td>Tipo de servicio</td><td>Mantenimiento preventivo, correctivo, instalación, diagnóstico o emergencia.</td></tr>
<tr><td>Equipos</td><td>Equipos a intervenir, identificados por código o QR.</td></tr>
<tr><td>Técnico asignado</td><td>Responsable de la ejecución.</td></tr>
<tr><td>Fecha y hora programadas</td><td>Ventana acordada con el cliente.</td></tr>
<tr><td>Prioridad</td><td>Normal, urgente o emergencia.</td></tr>
<tr><td>Descripción del trabajo</td><td>Alcance y falla reportada, si aplica.</td></tr>
</table>
<h2>3. Estados de la orden</h2>
<ol>
<li><strong>Pendiente:</strong> creada, sin técnico o fecha confirmada.</li>
<li><strong>Programada:</strong> con técnico y fecha asignados.</li>
<li><strong>En progreso:</strong> el técnico llegó al sitio e inició.</li>
<li><strong>Completada:</strong> trabajo terminado y reporte registrado.</li>
<li><strong>Cerrada:</strong> con conformidad del cliente y cobro gestionado.</li>
<li><strong>Cancelada:</strong> anulada, con motivo registrado.</li>
</ol>
<h2>4. Reglas</h2>
<ul>
<li>Todo trabajo adicional requiere aprobación del cliente antes de ejecutarse.</li>
<li>El técnico registra repuestos, insumos y refrigerante usados en la misma OT.</li>
<li>Una OT no se cierra sin reporte de servicio y acta de conformidad.</li>
</ul>
HTML,

'DOC-004' => <<<'HTML'
<h2>1. Propósito</h2>
<p>El Reporte de Servicio deja constancia de lo encontrado, lo realizado y lo recomendado en cada visita. Lo llena el técnico al terminar y se entrega al cliente.</p>
<h2>2. Contenido del reporte</h2>
<table>
<tr><th>Sección</th><th>Qué se registra</th></tr>
<tr><td>Datos generales</td><td>OT, cliente, sitio, fecha, hora de llegada y salida, técnico.</td></tr>
<tr><td>Equipo</td><td>Código, tipo, marca, modelo, serie y refrigerante.</td></tr>
<tr><td>Condición encontrada</td><td>Estado del equipo al llegar y falla reportada.</td></tr>
<tr><td>Mediciones</td><td>Voltaje, corriente, presiones, temperaturas de suministro y retorno.</td></tr>
<tr><td>Trabajo realizado</td><td>Actividades ejecutadas, paso a paso.</td></tr>
<tr><td>Repuestos e insumos</td><td>Materiales usados con cantidades.</td></tr>
<tr><td>Refrigerante</td><td>Tipo y kilos recuperados o cargados, con el cilindro utilizado.</td></tr>
<tr><td>Fotos</td><td>Antes y después, y evidencia de hallazgos.</td></tr>
<tr><td>Recomendaciones</td><td>Trabajos pendientes o mejoras sugeridas.</td></tr>
<tr><td>Resultado</td><td>Equipo operativo, operativo con observaciones o fuera de servicio.</td></tr>
</table>
<h2>3. Reglas</h2>
<ul>
<li>Se completa en la plataforma antes de salir del sitio, o dentro del mismo día si no hay señal.</li>
<li>Toda medición debe ir con su valor y unidad; no se escriben estimaciones.</li>
<li>Si el equipo queda fuera de servicio, se informa al cliente y se registra la causa.</li>
</ul>
HTML,

'DOC-005' => <<<'HTML'
<h2>1. Propósito</h2>
<p>El Acta de Conformidad confirma que el cliente recibió el trabajo y que se le explicó lo realizado. Es requisito para cerrar la orden de trabajo.</p>
<h2>2. Declaración</h2>
<p>El cliente declara que:</p>
<ol>
<li>Recibió el servicio descrito en la orden de trabajo y en el reporte de servicio.</li>
<li>El técnico le explicó los trabajos realizados y las recomendaciones.</li>
<li>Verificó, en lo que le fue posible, el funcionamiento del equipo.</li>
<li>El área de trabajo quedó limpia y ordenada.</li>
</ol>
<h2>3. Datos del acta</h2>
<table>
<tr><th>Campo</th><th>Descripción</th></tr>
<tr><td>OT</td><td>Número de la orden de trabajo.</td></tr>
<tr><td>Cliente</td><td>Nombre de quien recibe.</td></tr>
<tr><td>Fecha y hora</td><td>Momento de la entrega.</td></tr>
<tr><td>Observaciones del cliente</td><td>Comentarios o reservas, si las hay.</td></tr>
<tr><td>Conformidad</td><td>Conforme o conforme con observaciones.</td></tr>
<tr><td>Firma</td><td>Firma del cliente en pantalla o impresa.</td></tr>
</table>
<h2>4. Si el cliente no está conforme</h2>
<p>El técnico registra el motivo, avisa a la oficina y no cierra la orden. El caso se atiende como reclamo según el procedimiento de Garantías y Reclamos.</p>
HTML,

'DOC-006' => <<<'HTML'
<h2>1. Cobertura</h2>
<p>Control Total Servicios Integrales S. de R.L. garantiza que los trabajos descritos en este certificado fueron ejecutados con técnicas y materiales adecuados. Durante el plazo de garantía, corregirá sin costo los defectos de mano de obra comprobados.</p>
<h2>2. Plazos de garantía</h2>
<table>
<tr><th>Concepto</th><th>Plazo</th></tr>
<tr><td>Mano de obra de mantenimiento</td><td>30 días</td></tr>
<tr><td>Mano de obra de reparación</td><td>90 días</td></tr>
<tr><td>Mano de obra de instalación</td><td>6 meses</td></tr>
<tr><td>Repuestos y equipos nuevos</td><td>Según el fabricante o proveedor</td></tr>
</table>
<p>El plazo corre desde la fecha de cierre de la orden de trabajo. Los plazos pueden ajustarse según lo pactado en la cotización o el contrato.</p>
<h2>3. Qué no cubre</h2>
<ul>
<li>Daños por mal uso, golpes, humedad o instalaciones eléctricas deficientes del cliente.</li>
<li>Variaciones o picos de voltaje.</li>
<li>Intervención de personas ajenas a Control Total sobre el equipo o el trabajo.</li>
<li>Desgaste normal y falta de mantenimiento preventivo.</li>
<li>Fallas distintas de las que fueron reparadas.</li>
</ul>
<h2>4. Cómo solicitar la garantía</h2>
<ol>
<li>Comunicarse con Control Total al +504 8958 6548 o a controltotal@gmail.com indicando el número de OT.</li>
<li>Describir la falla y, si es posible, enviar fotos o video.</li>
<li>Se programa una visita de revisión; si el defecto está cubierto, se corrige sin costo.</li>
</ol>
<h2>5. Condiciones</h2>
<p>La garantía es válida solo con este certificado y su número de orden de trabajo. Cubre exclusivamente el trabajo y los equipos identificados en él.</p>
HTML,

];
