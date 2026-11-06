<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Contrato de servicio</title>
<style>
  body{font-family:system-ui,sans-serif;max-width:720px;margin:0 auto;padding:16px;color:#1a1a1a;line-height:1.45}
  table{width:100%;border-collapse:collapse;margin:8px 0}
  th,td{padding:6px 4px;border-bottom:1px solid #ddd;text-align:left;font-size:14px}
  .seccion{font-weight:bold;text-transform:uppercase;font-size:12px;letter-spacing:.05em;border-bottom:1px solid #ddd;margin:20px 0 8px;padding-bottom:3px}
  input[type=text]{width:100%;padding:8px;margin:4px 0 12px;box-sizing:border-box}
  canvas{border:1px solid #888;width:100%;height:160px;touch-action:none;background:#fff}
  button{padding:10px 18px;font-size:15px;border:0;border-radius:6px;cursor:pointer}
  .ok{background:#15803d;color:#fff}.no{background:#e5e7eb}
  .msg{padding:12px;border-radius:6px;background:#ecfdf5}.err{background:#fef2f2;color:#991b1b;padding:10px;border-radius:6px}
</style>
</head>
<body>
@if($resultado)
  <p class="msg">{{ $resultado }}</p>
@elseif(! $contrato)
  <h2>Enlace no disponible</h2>
  <p>El enlace no es válido o el contrato fue cancelado. Comuníquese con su asesor.</p>
@elseif($contrato->firmado_at)
  <h2>Contrato ya firmado</h2>
  <p>Firmado por {{ $contrato->firmado_por }} el {{ \Illuminate\Support\Carbon::parse($contrato->firmado_at)->format('d/m/Y H:i') }}.</p>
@else
  <h2>Contrato de servicio</h2>
  @include('contratos._texto', ['c' => $contrato])

  @if($errors->any())<p class="err">{{ $errors->first() }}</p>@endif

  <form method="POST" action="{{ route('contratos.firma.firmar', $token) }}" id="f">
    @csrf
    <div class="seccion">Firma del cliente</div>
    <p>Firma en representación de: <strong>{{ $contrato->cliente->razon_social ?? '' }}</strong></p>
    <label>Nombre completo de quien firma</label><input type="text" name="nombre" value="{{ old('nombre') }}" required>
    <label>Cargo</label><input type="text" name="cargo" value="{{ old('cargo') }}" required>
    <label>Firma</label>
    <canvas id="c"></canvas>
    <input type="hidden" name="firma" id="firma">
    <p><button type="button" class="no" id="limpiar">Borrar firma</button></p>
    <p><label><input type="checkbox" name="acepto" value="1" required> He leído y acepto los términos de este contrato.</label></p>
    <button class="ok" type="submit">Firmar contrato</button>
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
