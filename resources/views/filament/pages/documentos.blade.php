<x-filament-panels::page>
    <form wire:submit="guardar" class="space-y-6">
        {{ $this->form }}
        <x-filament::button type="submit">Guardar</x-filament::button>
    </form>

    <x-filament::section heading="Vista previa" description="Se genera con el registro más reciente, con la misma plantilla de producción. Para uno específico agrega ?id=NUMERO a la dirección.">
        <div class="flex flex-wrap gap-3">
            @foreach ($this->documentos() as $d)
                <x-filament::button tag="a" color="gray" target="_blank"
                    href="{{ route('documentos.previa', ['tipo' => $d['tipo']]) }}">
                    {{ $d['nombre'] }}
                </x-filament::button>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
