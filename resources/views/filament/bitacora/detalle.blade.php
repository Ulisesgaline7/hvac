@php
    $props = $record->properties?->toArray() ?? [];
    $cambios = $record->attribute_changes ?? null;
    $cambios = $cambios instanceof \Illuminate\Support\Collection ? $cambios->toArray() : $cambios;
    $json = fn ($v) => json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp

<div style="display:grid;gap:12px;font-size:14px;">
    <div>
        <strong>{{ $record->description }}</strong><br>
        <span style="opacity:.7">
            {{ $record->created_at?->format('d/m/Y H:i:s') }}
            · {{ $record->causer?->name ?? 'Sistema' }}
            · {{ $record->subject_type ? class_basename($record->subject_type) . ' #' . $record->subject_id : 'Sin módulo' }}
            @if ($record->event) · {{ $record->event }} @endif
        </span>
    </div>

    @if (! empty($props))
        <div>
            <div style="font-weight:600;margin-bottom:4px;">Propiedades</div>
            <pre style="font-size:12px;white-space:pre-wrap;word-break:break-word;margin:0;">{{ $json($props) }}</pre>
        </div>
    @endif

    @if (! empty($cambios))
        <div>
            <div style="font-weight:600;margin-bottom:4px;">Cambios</div>
            <pre style="font-size:12px;white-space:pre-wrap;word-break:break-word;margin:0;">{{ $json($cambios) }}</pre>
        </div>
    @endif

    @if (empty($props) && empty($cambios))
        <div style="opacity:.7">Este registro no tiene detalles adicionales.</div>
    @endif
</div>
