@php $uid = 'faq' . substr(md5(uniqid('', true)), 0, 8); @endphp
<div {{ \App\Support\SitioWeb::seccionAircon($d) }}>
    <div class="container">
        <div class="mx-auto wow fadeInUp" data-wow-delay="0.1s" style="max-width: 820px;">
            @if(filled($d['titulo'] ?? null))<h1 class="display-6 mb-5 text-center">{{ $d['titulo'] }}</h1>@endif
            <div class="accordion" id="{{ $uid }}">
                @foreach(collect($d['items'] ?? [])->values() as $i)
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="{{ $uid }}h{{ $loop->index }}">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $uid }}c{{ $loop->index }}" aria-expanded="false" aria-controls="{{ $uid }}c{{ $loop->index }}">{{ $i['pregunta'] ?? '' }}</button>
                        </h2>
                        <div id="{{ $uid }}c{{ $loop->index }}" class="accordion-collapse collapse" aria-labelledby="{{ $uid }}h{{ $loop->index }}" data-bs-parent="#{{ $uid }}">
                            <div class="accordion-body">{!! nl2br(e($i['respuesta'] ?? '')) !!}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
