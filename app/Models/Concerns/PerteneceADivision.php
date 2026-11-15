<?php

namespace App\Models\Concerns;

use App\Services\DivisionActiva;
use Illuminate\Database\Eloquent\Builder;

trait PerteneceADivision
{
    protected static function bootPerteneceADivision(): void
    {
        static::addGlobalScope('division', function (Builder $q) {
            $id = DivisionActiva::id();
            if ($id) {
                $q->where($q->getModel()->qualifyColumn('division_id'), $id);
            }
        });

        static::creating(function ($m) {
            $id = DivisionActiva::id();
            if ($id && empty($m->division_id)) {
                $m->division_id = $id;
            }
        });
    }
}
