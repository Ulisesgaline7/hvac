<div style="font-size:0.85rem;">
    <table style="width:100%; border-collapse:collapse;">
        <tr style="text-align:left;">
            <th style="padding:4px;">OT</th>
            <th style="padding:4px;">Concepto</th>
            <th style="padding:4px;">Rol</th>
            <th style="padding:4px; text-align:right;">%</th>
            <th style="padding:4px; text-align:right;">Monto (L)</th>
        </tr>
        @forelse ($liq->pagos->sortBy('orden_trabajo_id') as $p)
            <tr style="border-top:1px solid rgba(128,128,128,.3);">
                <td style="padding:4px;">{{ $p->ordenTrabajo?->numero ?? '—' }}</td>
                <td style="padding:4px;">{{ $p->concepto }}</td>
                <td style="padding:4px;">{{ ucfirst((string) $p->rol) }}</td>
                <td style="padding:4px; text-align:right;">{{ rtrim(rtrim(number_format((float) $p->porcentaje, 2), '0'), '.') }}</td>
                <td style="padding:4px; text-align:right;">{{ number_format((float) $p->monto, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" style="padding:6px;">Sin pagos asociados.</td></tr>
        @endforelse
    </table>
    <p style="margin-top:10px; text-align:right;">
        Bruto: <b>L {{ number_format((float) $liq->bruto, 2) }}</b>
        @if ($liq->retencion_pct)
            · Retención ({{ rtrim(rtrim(number_format((float) $liq->retencion_pct, 2), '0'), '.') }} %): <b>- L {{ number_format((float) $liq->retencion, 2) }}</b>
        @endif
        · Neto: <b>L {{ number_format((float) $liq->neto, 2) }}</b>
    </p>
</div>
