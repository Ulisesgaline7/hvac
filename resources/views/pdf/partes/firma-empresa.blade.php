@if (\App\Support\FirmaEmpresa::activa($documento))
    @if ($f = \App\Support\FirmaEmpresa::firma())
        <img src="{{ $f }}" style="height: 14mm;">
    @endif
    @if ($s = \App\Support\FirmaEmpresa::sello())
        <img src="{{ $s }}" style="height: 12mm; margin-left: 3mm;">
    @endif
    <br>{{ config('firmas.nombre') }} · {{ config('firmas.cargo') }}<br>
@endif
