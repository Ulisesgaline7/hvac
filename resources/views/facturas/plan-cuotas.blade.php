<div style="padding:4px 0;">
    <table style="width:100%; border-collapse:collapse; font-size:14px;">
        <thead>
            <tr style="text-align:left; border-bottom:1px solid #d1d5db;">
                <th style="padding:6px;">Cuota</th>
                <th style="padding:6px;">Vence</th>
                <th style="padding:6px; text-align:right;">Monto</th>
                <th style="padding:6px; text-align:right;">Saldo</th>
                <th style="padding:6px;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($filas as $fila)
                <tr style="border-bottom:1px solid #e5e7eb;">
                    <td style="padding:6px;">{{ $fila['cuota']->numero }}</td>
                    <td style="padding:6px;">{{ $fila['cuota']->vence->format('d/m/Y') }}</td>
                    <td style="padding:6px; text-align:right;">L {{ number_format((float) $fila['cuota']->monto, 2) }}</td>
                    <td style="padding:6px; text-align:right;">L {{ number_format($fila['saldo'], 2) }}</td>
                    <td style="padding:6px;">{{ ucfirst($fila['estado']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
