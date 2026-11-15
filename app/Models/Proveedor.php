<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Proveedor extends Model
{
    use LogsActivity;

    protected $table = 'proveedores';

    protected $fillable = ['nombre', 'rtn', 'contacto', 'telefono', 'email', 'direccion', 'notas', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['nombre', 'rtn', 'contacto', 'telefono', 'email', 'direccion', 'activo'])
            ->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function insumos(): HasMany
    {
        return $this->hasMany(Insumo::class);
    }
}
