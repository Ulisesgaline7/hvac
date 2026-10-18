<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\RegistraCreador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlantillaFormulario extends Model
{
    use Auditable, RegistraCreador, SoftDeletes;

    protected $table = 'plantillas_formulario';
    protected $guarded = [];

    public function versiones(): HasMany
    {
        return $this->hasMany(PlantillaVersion::class, 'plantilla_id');
    }

    public function ultimaVersion(): HasOne
    {
        return $this->hasOne(PlantillaVersion::class, 'plantilla_id')->ofMany('version', 'max');
    }

    /** Publica una versión nueva e inmutable. Las fichas ya llenadas conservan la suya. */
    public function publicar(array $campos): PlantillaVersion
    {
        return DB::transaction(function () use ($campos) {
            $siguiente = (int) $this->versiones()->max('version') + 1;

            return $this->versiones()->create([
                'version' => $siguiente,
                'definicion' => array_values($campos),
                'publicada_por' => Auth::id(),
            ]);
        });
    }
}
