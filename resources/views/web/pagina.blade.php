@extends(\App\Support\SitioWeb::vista('layout'))

@section('titulo', ($pagina->meta_titulo ?: $pagina->titulo) . ' | ' . \App\Support\SitioWeb::nombre())
@section('descripcion', $pagina->meta_descripcion ?: $pagina->resumen())
@if($pagina->imagen)
    @section('imagen_og', asset('storage/' . $pagina->imagen))
@endif

@section('contenido')
    @unless($pagina->publicada)
        <div style="background:#fef3c7;color:#92400e;padding:10px 16px;text-align:center;font-weight:600">Vista previa: esta página todavía no está publicada.</div>
    @endunless

    @if($pagina->mostrar_titulo)
        @include(\App\Support\SitioWeb::vista('partials.titulo-pagina'))
    @endif

    @if(! empty($pagina->bloques))
        @include('web.partials.bloques')
    @elseif(filled($pagina->contenido))
        @include(\App\Support\SitioWeb::vista('partials.contenido-legacy'))
    @endif
@endsection
