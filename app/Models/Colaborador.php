<?php

namespace App\Models;

use App\Models\Concerns\RegistraCreador;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Colaborador extends Model
{
    use LogsActivity, RegistraCreador, SoftDeletes;

    protected $table = 'colaboradores';
    protected $guarded = [];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'identidad' => 'encrypted',
        'cuenta_bancaria' => 'encrypted',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['identidad', 'cuenta_bancaria'])
            ->logOnlyDirty();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vinculos(): HasMany
    {
        return $this->hasMany(Vinculo::class)->orderByDesc('fecha_inicio');
    }

    public function vinculoActivo(): HasOne
    {
        return $this->hasOne(Vinculo::class)->whereNull('fecha_fin');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoColaborador::class);
    }

    /**
     * Cierra el vínculo abierto (si hay) y abre uno nuevo, en una sola transacción.
     * El vínculo anterior termina el día antes de $desde (o el mismo día en que empezó,
     * si el cambio es el mismo día del alta).
     */
    public function cambiarVinculo(
        string $tipo,
        string $cargo,
        Carbon $desde,
        ?string $remuneracion = null,
        ?float $monto = null,
        ?float $comisionPct = null,
        ?string $motivo = null,
    ): Vinculo {
        return DB::transaction(function () use ($tipo, $cargo, $desde, $remuneracion, $monto, $comisionPct, $motivo) {
            $actual = $this->vinculos()->whereNull('fecha_fin')->lockForUpdate()->first();

            if ($actual) {
                if ($desde->lt($actual->fecha_inicio)) {
                    throw new \DomainException('La fecha de inicio no puede ser anterior al inicio del vínculo actual.');
                }

                $fin = $desde->copy()->subDay();
                if ($fin->lt($actual->fecha_inicio)) {
                    $fin = $actual->fecha_inicio->copy();
                }

                $actual->update(['fecha_fin' => $fin, 'motivo_fin' => $motivo ?? 'Cambio de vínculo']);
            }

            return $this->vinculos()->create([
                'tipo' => $tipo,
                'cargo' => $cargo,
                'fecha_inicio' => $desde,
                'remuneracion' => $remuneracion,
                'monto' => $monto,
                'comision_pct' => $comisionPct,
            ]);
        });
    }
}
