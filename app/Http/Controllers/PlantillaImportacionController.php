<?php

namespace App\Http\Controllers;

use App\Services\ImportadorEquipos;
use Illuminate\Support\Facades\Auth;

class PlantillaImportacionController extends Controller
{
    public function __invoke()
    {
        if (! Auth::check()) {
            return redirect()->guest(route('filament.admin.auth.login'));
        }
        abort_unless(Auth::user()->hasAnyRole(['administrador', 'coordinador']), 403);

        $filas = [
            ImportadorEquipos::COLUMNAS,
            ['Restaurante Demo', 'Sucursal Centro', 'Cocina', 'Split', 'Carrier', '42QHC018', 'SN12345', '18000', 'BTU', 'R-410A', '220V', '9A', '2025-03-15', '2028-03-15', 'operativo'],
        ];

        $csv = "\xEF\xBB\xBF" . implode("\n", array_map(fn ($f) => implode(';', $f), $filas)) . "\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla-equipos.csv"',
        ]);
    }
}
