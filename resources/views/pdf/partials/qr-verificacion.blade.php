@php
    $url = url('/v/' . $token);
    try { $qrImg = app(\App\Services\GeneradorQr::class)->dataUriDeUrl($url); } catch (\Throwable $e) { $qrImg = null; }
@endphp
@if ($qrImg)
    <table style="margin-top:10px; page-break-inside: avoid;"><tr>
        <td style="width:70px;"><img src="{{ $qrImg }}" style="width:64px; height:64px;"></td>
        <td style="vertical-align:middle; color:#4B5A6E; font-size:7.6pt;">
            Verifica la autenticidad de {{ $que ?? 'este documento' }} escaneando el código o visitando<br>{{ $url }}
        </td>
    </tr></table>
@endif
