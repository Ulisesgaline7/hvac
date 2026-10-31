<section class="sec">
    <div class="wrap">
        <article class="pagina">
            <h1>{{ $pagina->titulo }}</h1>
            @if($pagina->imagen)
                <img class="portada" src="{{ asset('storage/' . $pagina->imagen) }}" alt="{{ $pagina->titulo }}">
            @endif
        </article>
    </div>
</section>
