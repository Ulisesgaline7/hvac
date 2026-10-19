<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cotización {{ $cotizacion->numero ?? '' }}</title>
<style>
  body{font-family:system-ui,sans-serif;max-width:720px;margin:0 auto;padding:16px;color:#1a1a1a}
  table{width:100%;border-collapse:collapse;margin:12px 0}
  th,td{padding:6px 4px;border-bottom:1px solid #ddd;text-align:left;font-size:14px}
  td.n,th.n{text-align:right}
  input[type=text],textarea{width:100%;padding:8px;margin:4px 0 12px;box-sizing:border-box}
  canvas{border:1px solid #888;width:100%;height:160px;touch-action:none;background:#fff}
  button{padding:10px 18px;font-size:15px;border:0;border-radius:6px;cursor:pointer}
  .ok{background:#15803d;color:#fff}.no{background:#e5e7eb}
  .msg{padding:12px;border-radius:6px;background:#ecfdf5}.err{background:#fef2f2;color:#991b1b;padding:10px;border-radius:6px}
</style>
</head>
<body>
@if($resultado)
  <p class="msg">{{ $resultado }}</p>
@elseif(! $cotizacion)
  <h2>Enlace no disponible</h2>
  <p>Esta cotización ya fue respondida, venció o el enlace no es válido. Comuníquese con su asesor.</p>
@else
  <h2>Cotización {{ $cotizacion->numero }}</h2>
  <p>{{ $cotizacion->cliente->razon_social ?? '' }}@if($cotizacion->sucursal) — {{ $cotizacion->sucursal->nombre ?? '' }}@endif<br>
     Válida hasta: {{ optional($cotizacion->valida_hasta)->format('d/m/Y') }}</p>
  <table>
    <tr><th>Descripción</th><th class="n">Cant.</th><th class="n">Precio</th><th class="n">Importe</th></tr>
    @foreach($cotizacion->lineas as $l)
      <tr><td>{{ $l->descripcion }}</td><td class="n">{{ rtrim(rtrim((string) $l->cantidad, '0'), '.') }}</td>
          <td class="n">{{ number_format($l->precio_unitario, 2) }}</td><td class="n">{{ number_format($l->importe, 2) }}</td></tr>
    @endforeach
    @if ($cotizacion->visita_tratamiento === 'cobrar' && (float) $cotizacion->visita_monto > 0)
      <tr><td colspan="3" class="n">Visita técnica</td><td class="n">{{ number_format($cotizacion->visita_monto, 2) }}</td></tr>
    @elseif (in_array($cotizacion->visita_tratamiento, ['bonificar', 'gratis'], true))
      <tr><td colspan="3" class="n">Visita técnica (sin costo)</td><td class="n">0.00</td></tr>
    @endif
    <tr><td colspan="3" class="n">ISV</td><td class="n">{{ number_format($cotizacion->isv, 2) }}</td></tr>
    <tr><td colspan="3" class="n"><strong>Total</strong></td><td class="n"><strong>L {{ number_format($cotizacion->total, 2) }}</strong></td></tr>
  </table>
  @if($cotizacion->condiciones)<p><strong>Condiciones:</strong><br>{!! nl2br(e($cotizacion->condiciones)) !!}</p>@endif

  @if($errors->any())<p class="err">{{ $errors->first() }}</p>@endif

  <form method="POST" action="{{ route('cotizaciones.aceptar', $token) }}" id="f">
    @csrf
    <label>Nombre completo</label><input type="text" name="nombre" value="{{ old('nombre') }}" required>
    <label>Cargo</label><input type="text" name="cargo" value="{{ old('cargo') }}" required>
    <label>Firma</label>
    <canvas id="c"></canvas>
    <input type="hidden" name="firma" id="firma">
    <p><button type="button" class="no" id="limpiar">Borrar firma</button></p>
    <p><label><input type="checkbox" name="acepto" value="1" required> Acepto los términos, condiciones y el monto de esta cotización.</label></p>
    <button class="ok" type="submit">Aceptar cotización</button>
  </form>

  <form method="POST" action="{{ route('cotizaciones.rechazar', $token) }}" style="margin-top:28px">
    @csrf
    <label>¿No desea aceptarla? Cuéntenos el motivo (opcional)</label>
    <textarea name="motivo" rows="2"></textarea>
    <button class="no" type="submit">Rechazar cotización</button>
  </form>

<script>
const c=document.getElementById('c'),x=c.getContext('2d');let d=false,t=false;
function size(){const r=c.getBoundingClientRect();c.width=r.width;c.height=r.height;x.lineWidth=2;x.lineCap='round';}
size();
function p(e){const r=c.getBoundingClientRect();return[e.clientX-r.left,e.clientY-r.top];}
c.addEventListener('pointerdown',e=>{d=true;t=true;x.beginPath();x.moveTo(...p(e));c.setPointerCapture(e.pointerId);});
c.addEventListener('pointermove',e=>{if(!d)return;x.lineTo(...p(e));x.stroke();});
['pointerup','pointercancel'].forEach(n=>c.addEventListener(n,()=>d=false));
document.getElementById('limpiar').onclick=()=>{x.clearRect(0,0,c.width,c.height);t=false;};
document.getElementById('f').addEventListener('submit',e=>{
  if(!t){e.preventDefault();alert('Por favor dibuje su firma.');return;}
  document.getElementById('firma').value=c.toDataURL('image/png');
});
</script>
@endif
</body>
</html>
