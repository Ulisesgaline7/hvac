<?php

namespace App\Http\Controllers;

use App\Services\OfflineTecnico;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfflineTecnicoController extends Controller
{
    public function paquete(Request $request, OfflineTecnico $servicio): JsonResponse
    {
        try {
            return $this->sinCache(response()->json($servicio->paquete($request->user())));
        } catch (DomainException $e) {
            return response()->json(['mensaje' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    public function ficha(Request $request, OfflineTecnico $servicio): JsonResponse
    {
        $datos = $request->validate([
            'linea_id' => ['required', 'integer'],
            'version_id' => ['required', 'integer'],
            'respuestas' => ['present', 'array'],
            'firmas' => ['nullable', 'array', 'max:5'],
            'firmas.*' => ['string', 'starts_with:data:image/png;base64,', 'max:1400000'],
        ]);

        try {
            $ficha = $servicio->guardarFicha(
                $request->user(), $datos['linea_id'], $datos['version_id'], $datos['respuestas'], $datos['firmas'] ?? [],
            );
        } catch (DomainException $e) {
            return response()->json(['mensaje' => $e->getMessage()], $e->getCode() ?: 422);
        }

        return $this->sinCache(response()->json([
            'completa' => (bool) $ficha->completa,
            'alertas' => $ficha->alertas ?? [],
            'respuestas' => (object) ($ficha->respuestas ?? []),
        ]));
    }

    private function sinCache(JsonResponse $r): JsonResponse
    {
        return $r->header('Cache-Control', 'no-store, private');
    }
}
