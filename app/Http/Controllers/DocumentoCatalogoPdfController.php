<?php

namespace App\Http\Controllers;

use App\Models\DocumentoCatalogo;
use App\Services\DocumentoPdf;
use Illuminate\Support\Facades\Auth;

class DocumentoCatalogoPdfController extends Controller
{
    public function __invoke(DocumentoCatalogo $documento, DocumentoPdf $pdf)
    {
        $u = Auth::user();
        abort_unless($u?->hasAnyRole(['administrador', 'coordinador', 'tecnico']), 403);

        // El personal de campo solo ve los documentos vigentes.
        if (! $u->hasAnyRole(['administrador', 'coordinador'])) {
            abort_unless($documento->estado === 'vigente', 403);
        }

        return response($pdf->render($documento), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . ($documento->codigo_interno ?: $documento->codigo) . '.pdf"',
        ]);
    }
}
