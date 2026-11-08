<?php

namespace App\Http\Controllers;

use App\Providers\RlsServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DocumentoOtController extends Controller
{
    public function __invoke(int $media)
    {
        RlsServiceProvider::aplicar(auth()->user());

        // Con RLS (media_ot_*) un documento de una OT ajena no se encuentra (404).
        $m = Media::findOrFail($media);

        abort_unless($m->model_type === \App\Models\OrdenTrabajo::class && $m->collection_name === 'documentos_ot', 404);

        $ruta = $m->getPath();
        abort_unless(is_file($ruta), 404);

        return response()->file($ruta, [
            'Content-Type' => $m->mime_type,
            'Content-Disposition' => 'inline; filename="' . $m->file_name . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
