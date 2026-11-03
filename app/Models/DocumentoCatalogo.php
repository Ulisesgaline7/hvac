<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentoCatalogo extends Model
{
    protected $table = 'documentos_catalogo';
    protected $guarded = [];
    protected $casts = ['vigente_desde' => 'date', 'proxima_revision' => 'date'];

    public const DISENOS = [
        'comercial' => 'Comercial',
        'contrato' => 'Contrato',
        'formulario' => 'Formulario con firmas',
        'controlado' => 'Documento controlado',
        'registro' => 'Lista o registro',
        'pantalla' => 'Pantalla o reporte',
    ];

    public const ESTADOS = [
        'pendiente' => 'Pendiente (sin contenido)',
        'en_progreso' => 'En progreso',
        'vigente' => 'Vigente',
        'obsoleto' => 'Obsoleto',
        'fuera' => 'Fuera del sistema',
    ];

    protected static function booted(): void
    {
        // Antes de cambiar el contenido se guarda una copia de la versión anterior.
        static::updating(function (self $d) {
            if ($d->isDirty('contenido') && filled($d->getOriginal('contenido'))) {
                DocumentoVersion::create([
                    'documento_id' => $d->id,
                    'version' => $d->getOriginal('version'),
                    'contenido' => $d->getOriginal('contenido'),
                    'creado_por' => auth()->id(),
                ]);
            }
        });
    }

    public function versiones(): HasMany
    {
        return $this->hasMany(DocumentoVersion::class, 'documento_id')->latest('id');
    }

    public function etiquetaDiseno(): string
    {
        return self::DISENOS[$this->diseno] ?? 'Documento';
    }
}
