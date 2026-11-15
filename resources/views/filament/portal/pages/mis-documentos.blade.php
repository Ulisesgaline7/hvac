<x-filament-panels::page>
    @php
        $carpetas = [
            'Contrato' => 'Contratos',
            'Cotización' => 'Cotizaciones',
            'Factura' => 'Facturación',
            'Recibo de pago' => 'Facturación',
            'Informe de servicio' => 'Servicios y garantías',
            'Certificado de garantía' => 'Servicios y garantías',
        ];
        $arbol = collect($this->documentos())
            ->groupBy(fn ($f) => $carpetas[$f[0]] ?? 'Otros')
            ->map(fn ($g) => $g->groupBy(fn ($f) => $f[4] ? substr((string) $f[4], 0, 4) : 'Sin fecha')->sortKeysDesc())
            ->sortKeys();
    @endphp

    @forelse ($arbol as $carpeta => $anios)
        <x-filament::section :heading="$carpeta" icon="heroicon-o-folder" collapsible>
            @foreach ($anios as $anio => $filas)
                <x-filament::section :heading="$anio . ' (' . count($filas) . ')'" icon="heroicon-o-folder-open" collapsible collapsed>
                    <table class="w-full text-sm text-left">
                        <tbody>
                            @foreach ($filas as $fila)
                                <tr class="border-b border-gray-100 dark:border-white/5">
                                    <td class="py-2 pr-4">{{ $fila[0] }}</td>
                                    <td class="py-2 pr-4">{{ $fila[1] }}</td>
                                    <td class="py-2 pr-4">{{ $fila[2] }}</td>
                                    <td class="py-2"><a href="{{ $fila[3] }}" target="_blank" class="text-primary-600 hover:underline">Ver / descargar PDF</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-filament::section>
            @endforeach
        </x-filament::section>
    @empty
        <x-filament::section><p>Todavía no hay documentos emitidos.</p></x-filament::section>
    @endforelse
</x-filament-panels::page>
