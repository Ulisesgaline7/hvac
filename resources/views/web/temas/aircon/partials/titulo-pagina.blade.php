<div class="container-xxl pt-5 pb-0">
    <div class="container">
        <h1 class="display-6 mb-4">{{ $pagina->titulo }}</h1>
        @if($pagina->imagen)
            <img class="img-fluid rounded w-100 mb-2" src="{{ asset('storage/' . $pagina->imagen) }}" alt="{{ $pagina->titulo }}">
        @endif
    </div>
</div>
