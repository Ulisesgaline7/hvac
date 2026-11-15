<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Verificación de cotización {{ $numero }}</title>
<style>
body{font-family:system-ui,sans-serif;background:#f3f4f6;margin:0;color:#111827}
main{max-width:760px;margin:0 auto;padding:1.25rem}
.card{background:#fff;border-radius:.75rem;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.12);margin-bottom:1rem}
.marca{color:#6b7280;font-size:.85rem;margin:0 0 .25rem}
h1{font-size:1.4rem;margin:0 0 .5rem}
.estado{display:inline-block;color:#fff;padding:.2rem .7rem;border-radius:999px;font-weight:600;font-size:.9rem}
dl{display:grid;grid-template-columns:auto 1fr;gap:.35rem 1rem;margin:1rem 0 0}
dt{color:#6b7280}dd{margin:0;font-weight:500}
.ok{background:#ecfdf5;color:#065f46;border:1px solid #6ee7b7}
.mal{background:#fef2f2;color:#991b1b;border:1px solid #fca5a5}
.aviso{padding:.8rem 1rem;border-radius:.5rem;margin-top:1rem}
.boton{display:inline-block;background:#111827;color:#fff;text-decoration:none;padding:.6rem 1rem;border-radius:.5rem}
object{width:100%;height:70vh;border:1px solid #e5e7eb;border-radius:.5rem;margin-top:1rem}
small{color:#6b7280}
</style>
</head>
<body>
<main>
<div class="card">
    <p class="marca">{{ \App\Support\Marca::nombre() }} · Verificación de documento</p>
    <h1>Cotización {{ $numero }}</h1>
    <span class="estado" style="background: {{ $color }};">{{ $estado }}</span>
    <dl>
        <dt>Cliente</dt><dd>{{ $cliente }}</dd>
        @if ($fecha)<dt>Fecha</dt><dd>{{ $fecha->format('d/m/Y') }}</dd>@endif
        @if ($valida)<dt>Válida hasta</dt><dd>{{ $valida->format('d/m/Y') }}</dd>@endif
        <dt>Total</dt><dd>L {{ number_format($total, 2) }}</dd>
        @if ($emitida)<dt>Emitida</dt><dd>{{ $emitida->format('d/m/Y H:i') }}</dd>@endif
    </dl>
    @if ($integro)
        <div class="aviso ok">Este es el documento original registrado al emitirlo. Compárelo con su copia: si algún dato difiere, la copia fue alterada y no es válida.</div>
    @else
        <div class="aviso mal"><strong>Alerta:</strong> el archivo original no coincide con su registro. No considere válido ningún documento con este código y comuníquese con la empresa.</div>
    @endif
    @if ($integro)
        <p style="margin-top:1rem"><a class="boton" href="{{ $urlPdf }}">Abrir el PDF original</a></p>
        <small>Huella digital: {{ $huella }}…</small>
    @endif
</div>
@if ($integro)
<object data="{{ $urlPdf }}" type="application/pdf"></object>
@endif
</main>
</body>
</html>
