<?php

namespace App\Filament\Concerns;

use DomainException;
use Illuminate\Database\Eloquent\Model;

/** Guarda un registro de un RelationManager y convierte los campos f_* (FirmaPad) en archivos de media. */
trait GuardaFirmas
{
    protected function guardarConFirmas(?Model $registro, array $data, array $colecciones): Model
    {
        $firmas = [];
        foreach ($colecciones as $coleccion) {
            $firmas[$coleccion] = $data['f_' . $coleccion] ?? null;
            unset($data['f_' . $coleccion]);
        }

        foreach ($firmas as $valor) {
            if (is_string($valor) && strlen($valor) > 2_000_000) {
                throw new DomainException('La firma es demasiado grande.');
            }
        }

        if ($registro) {
            $registro->update($data);
        } else {
            $registro = $this->getOwnerRecord()->{static::$relationship}()->create($data);
        }

        foreach ($firmas as $coleccion => $valor) {
            if (is_string($valor) && str_starts_with($valor, 'data:image/png;base64,')) {
                $registro->clearMediaCollection($coleccion);
                $registro->addMediaFromBase64($valor, ['image/png'])
                    ->usingFileName($coleccion . '.png')
                    ->toMediaCollection($coleccion, 'local');
            }
        }

        return $registro;
    }
}
