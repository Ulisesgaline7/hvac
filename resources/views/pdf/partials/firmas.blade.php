@php $f = \App\Support\FirmaEmpresa::firmantes($doc); @endphp
@if ($f)
<table style="width:100%; margin-top:24px; text-align:center; font-size:8pt; page-break-inside:avoid;">
  <tr>
    @foreach (['tecnico' => 'Técnico', 'supervisor' => 'Supervisor', 'administrador' => 'Administrador'] as $k => $titulo)
      @if (in_array($k, $f, true))
        @php $img = ${'firma_' . $k} ?? null; @endphp
        <td style="width:26%; vertical-align:bottom;">
          @if ($img)<img src="{{ $img }}" style="max-height:55px;">@else<div style="height:55px;"></div>@endif
          <div class="firma">{{ $titulo }}</div>
        </td>
      @endif
    @endforeach
    @if (in_array('sello', $f, true) && ($s = \App\Support\FirmaEmpresa::sello()))
      <td style="width:22%;"><img src="{{ $s }}" style="max-height:85px;"></td>
    @endif
  </tr>
</table>
@endif
