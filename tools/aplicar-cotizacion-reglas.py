#!/usr/bin/env python3
"""Aplica los cambios de movilización, viáticos y planes a archivos existentes.
Si algún texto de referencia no se encuentra exactamente una vez, no modifica nada."""
import sys, pathlib

R = pathlib.Path('.')
MARCA = 'viaticos_detallados'

cambios = {}

def c(archivo, ancla, nuevo, despues=False):
    cambios.setdefault(archivo, []).append((ancla, nuevo, despues))

c('config/tarifario.php',
  "    'viaticos_dia' => 600,\n",
  "    // Movilización y viáticos detallados (propuesta). 0 = desactivado, 1 = activado. Se editan en el panel.\n"
  "    'viaticos_detallados' => 0,\n    'km_tarifa' => 12,\n    'alimentacion_dia' => 350,\n    'hospedaje_noche' => 900,\n",
  despues=True)

c('config/tarifario.php',
  "        'CONTRATO_4' => ['nombre' => 'Contrato anual (4 visitas por equipo)', 'min' => 0.12, 'max' => 0.15],\n",
  "        'PLAN_ESENCIAL' => ['nombre' => 'Plan Esencial (4 visitas al año, cada 3 meses)', 'min' => 0.10, 'max' => 0.10],\n"
  "        'PLAN_EMPRESARIAL' => ['nombre' => 'Plan Empresarial (6 visitas al año, cada 2 meses)', 'min' => 0.12, 'max' => 0.12],\n"
  "        'PLAN_PREMIUM' => ['nombre' => 'Plan Premium (12 visitas al año, mensual)', 'min' => 0.15, 'max' => 0.15],\n",
  despues=True)

c('app/Models/ReglaTarifario.php',
  "            'servicio_minimo', 'viaticos_dia' => $num($valor),",
  "            'servicio_minimo', 'viaticos_dia', 'km_tarifa', 'alimentacion_dia', 'hospedaje_noche' => $num($valor),")
c('app/Models/ReglaTarifario.php',
  "            'minimo_si_menos_de', 'validez_dias_habiles' => (int) $valor,",
  "            'minimo_si_menos_de', 'validez_dias_habiles', 'viaticos_detallados' => (int) $valor,")

c('app/Models/Cotizacion.php',
  "        'recargos' => 'array',\n",
  "        'logistica' => 'array',\n        'logistica_detalle' => 'array',\n",
  despues=True)

c('app/Services/CotizacionService.php',
  "        $subtotal = round($r['subtotal'] + $visita, 2);\n"
  "        $isv = $visita > 0 ? round($subtotal * config('tarifario.isv'), 2) : $r['isv'];\n",
  "        $extra = (new LogisticaViaticos)->calcular($c->logistica ?? [], config('tarifario'));\n"
  "        $adicional = round($visita + $extra['total'], 2);\n"
  "        $subtotal = round($r['subtotal'] + $adicional, 2);\n"
  "        $isv = $adicional > 0 ? round($subtotal * config('tarifario.isv'), 2) : $r['isv'];\n")
c('app/Services/CotizacionService.php',
  "            'visita_monto' => $visita,\n        ]);",
  "            'visita_monto' => $visita,\n"
  "            'logistica_monto' => $extra['movilizacion'],\n"
  "            'viaticos_monto' => $extra['viaticos'],\n"
  "            'logistica_detalle' => $extra['total'] > 0 ? $extra['detalle'] : null,\n        ]);")

c('app/Filament/Resources/Cotizacions/Schemas/CotizacionForm.php',
  "            TextInput::make('subtotal')->label('Subtotal')->prefix('L')->disabled()->dehydrated(false)->visibleOn('edit'),",
  "            TextInput::make('visitas_anuales')->label('Visitas al año (solo planes)')->numeric()->integer()->minValue(1)->maxValue(24)\n"
  "                ->helperText('Si se indica, el PDF muestra también el total anual.')\n"
  "                ->disabled($bloqueada),\n"
  "            TextInput::make('logistica.km')->label('Kilómetros adicionales de la ruta')->numeric()->minValue(0)->suffix('km')\n"
  "                ->helperText('Recorrido adicional que genera la ruta, no un viaje completo por sede.')\n"
  "                ->visible(fn () => (bool) config('tarifario.viaticos_detallados'))->disabled($bloqueada),\n"
  "            TextInput::make('logistica.tecnicos')->label('Técnicos que viajan')->numeric()->integer()->minValue(0)\n"
  "                ->visible(fn () => (bool) config('tarifario.viaticos_detallados'))->disabled($bloqueada),\n"
  "            TextInput::make('logistica.dias')->label('Días de trabajo fuera de la zona')->numeric()->integer()->minValue(0)\n"
  "                ->visible(fn () => (bool) config('tarifario.viaticos_detallados'))->disabled($bloqueada),\n"
  "            TextInput::make('logistica.noches')->label('Noches de hospedaje')->numeric()->integer()->minValue(0)\n"
  "                ->helperText('Déjelo en 0 si regresan el mismo día.')\n"
  "                ->visible(fn () => (bool) config('tarifario.viaticos_detallados'))->disabled($bloqueada),\n"
  "            TextInput::make('logistica.peajes')->label('Peajes y estacionamiento')->numeric()->minValue(0)->prefix('L')\n"
  "                ->visible(fn () => (bool) config('tarifario.viaticos_detallados'))->disabled($bloqueada),\n\n",
  despues=False)

c('resources/views/cotizaciones/pdf.blade.php',
  "    @if ($c->visita_tratamiento === 'cobrar' && (float) $c->visita_monto > 0)",
  "    @if ((float) $c->logistica_monto > 0 || (float) $c->viaticos_monto > 0)\n"
  "        @php($lg = $c->logistica_detalle ?? [])\n"
  "        @if ((float) $c->logistica_monto > 0)\n"
  "            <tr><td>Movilización ({{ rtrim(rtrim(number_format((float) ($lg['km'] ?? 0), 2, '.', ''), '0'), '.') }} km × L {{ number_format((float) ($lg['tarifa_km'] ?? 0), 2) }})</td><td class=\"r\">L {{ number_format((float) $c->logistica_monto, 2) }}</td></tr>\n"
  "        @endif\n"
  "        @if ((float) ($lg['alimentacion'] ?? 0) > 0)\n"
  "            <tr><td>Alimentación ({{ $lg['tecnicos'] ?? 0 }} técnicos × {{ $lg['dias'] ?? 0 }} días × L {{ number_format((float) ($lg['tarifa_alimentacion'] ?? 0), 2) }})</td><td class=\"r\">L {{ number_format((float) $lg['alimentacion'], 2) }}</td></tr>\n"
  "        @endif\n"
  "        @if ((float) ($lg['hospedaje'] ?? 0) > 0)\n"
  "            <tr><td>Hospedaje ({{ $lg['tecnicos'] ?? 0 }} técnicos × {{ $lg['noches'] ?? 0 }} noches × L {{ number_format((float) ($lg['tarifa_hospedaje'] ?? 0), 2) }})</td><td class=\"r\">L {{ number_format((float) $lg['hospedaje'], 2) }}</td></tr>\n"
  "        @endif\n"
  "        @if ((float) ($lg['peajes'] ?? 0) > 0)\n"
  "            <tr><td>Peajes y estacionamiento</td><td class=\"r\">L {{ number_format((float) $lg['peajes'], 2) }}</td></tr>\n"
  "        @endif\n"
  "    @endif\n"
  "    @if ($c->visita_tratamiento === 'cobrar' && (float) $c->visita_monto > 0)")

c('resources/views/cotizaciones/pdf.blade.php',
  "    <tr class=\"total\"><td>TOTAL</td><td class=\"r\">L {{ number_format((float) $c->total, 2) }}</td></tr>\n",
  "    @if ((int) $c->visitas_anuales > 1)\n"
  "        @php($anual = \\App\\Services\\LogisticaViaticos::anual((float) $c->subtotal, (int) $c->visitas_anuales, (float) config('tarifario.isv')))\n"
  "        <tr><td>Visitas al año</td><td class=\"r\">{{ (int) $c->visitas_anuales }}</td></tr>\n"
  "        <tr><td>Subtotal anual</td><td class=\"r\">L {{ number_format($anual['subtotal'], 2) }}</td></tr>\n"
  "        <tr><td>ISV anual ({{ (int) (config('tarifario.isv') * 100) }} %)</td><td class=\"r\">L {{ number_format($anual['isv'], 2) }}</td></tr>\n"
  "        <tr class=\"total\"><td>TOTAL ANUAL</td><td class=\"r\">L {{ number_format($anual['total'], 2) }}</td></tr>\n"
  "    @endif\n",
  despues=True)

def main():
    nuevos = {}
    errores = []
    for archivo, lista in cambios.items():
        p = R / archivo
        if not p.exists():
            errores.append(f'No existe {archivo}'); continue
        texto = p.read_text(encoding='utf-8')
        for ancla, nuevo, despues in lista:
            veces = texto.count(ancla)
            if veces != 1:
                errores.append(f'{archivo}: el texto de referencia aparece {veces} veces (debe ser 1):\n    {ancla.strip()[:90]}')
                continue
            texto = texto.replace(ancla, ancla + nuevo if despues else nuevo + ancla if nuevo.endswith('\n\n') else nuevo)
        nuevos[archivo] = texto
    if errores:
        print('NO SE MODIFICO NADA. Problemas:'); [print(' -', e) for e in errores]; sys.exit(1)
    for archivo, texto in nuevos.items():
        (R / archivo).write_text(texto, encoding='utf-8'); print('modificado', archivo)

if __name__ == '__main__':
    if (R / 'config/tarifario.php').exists() and "'viaticos_detallados'" in (R / 'config/tarifario.php').read_text(encoding='utf-8'):
        print('Ya aplicado (config/tarifario.php ya tiene viaticos_detallados). No se hace nada.'); sys.exit(0)
    main()
