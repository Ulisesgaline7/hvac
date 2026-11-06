@extends(\App\Support\SitioWeb::vista('layout'))

@section('titulo', 'Página no encontrada | ' . \App\Support\SitioWeb::nombre())
@section('descripcion', 'La página que buscas no existe o fue movida.')

@section('contenido')
<section style="padding:90px 16px;text-align:center">
    <h1 style="font-size:72px;margin:0">404</h1>
    <h2 style="margin:8px 0 12px">No encontramos esa página</h2>
    <p>Puede que el enlace haya cambiado o que la dirección esté mal escrita.</p>
    <p><a href="{{ route('home') }}" style="font-weight:700;text-decoration:underline">Volver al inicio</a></p>
</section>
@endsection
