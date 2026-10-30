@extends('web.layout')

@section('titulo', ($pagina->meta_titulo ?: $pagina->titulo) . ' | ' . \App\Support\SitioWeb::nombre())
@section('descripcion', $pagina->meta_descripcion ?: \Illuminate\Support\Str::limit(trim(strip_tags((string) $pagina->contenido)), 160))
@if($pagina->imagen)
    @section('imagen_og', asset('storage/' . $pagina->imagen))
@endif

@section('contenido')
    <section class="sec">
        <div class="wrap">
            <article class="pagina">
                <h1>{{ $pagina->titulo }}</h1>
                @if($pagina->imagen)
                    <img class="portada" src="{{ asset('storage/' . $pagina->imagen) }}" alt="{{ $pagina->titulo }}">
                @endif
                <div class="pagina-contenido">{!! $pagina->contenido !!}</div>
            </article>
        </div>
    </section>
@endsection
