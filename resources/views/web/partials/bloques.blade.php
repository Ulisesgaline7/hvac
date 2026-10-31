@foreach(collect($pagina->bloques ?? [])->values() as $bloque)
    @php
        $tipo = $bloque['type'] ?? null;
        $d = $bloque['data'] ?? [];
        $vistaBloque = ($tipo && preg_match('/^[a-z_]+$/', $tipo)) ? \App\Support\SitioWeb::vista('bloques.' . $tipo) : null;
    @endphp
    @if($vistaBloque && view()->exists($vistaBloque))
        @include($vistaBloque, ['d' => $d])
    @endif
@endforeach
