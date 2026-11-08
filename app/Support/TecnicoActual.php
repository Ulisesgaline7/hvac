<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Resuelve el técnico (tabla tecnicos) del usuario con sesión. */
final class TecnicoActual
{
    /** @var array<int, ?int> */
    private static array $cache = [];

    public static function id(): ?int
    {
        $u = auth()->user();
        if (! $u) {
            return null;
        }
        if (array_key_exists($u->id, self::$cache)) {
            return self::$cache[$u->id];
        }

        $q = DB::table('tecnicos');
        if (Schema::hasColumn('tecnicos', 'user_id')) {
            $q->where('user_id', $u->id);
        } elseif (Schema::hasColumn('tecnicos', 'email')) {
            $q->where('email', $u->email);
        } else {
            return self::$cache[$u->id] = null;
        }

        $id = $q->value('id');

        return self::$cache[$u->id] = ($id ? (int) $id : null);
    }

    /** IDs de OT donde participa (líder, equipo o supervisor). */
    public static function otIds()
    {
        $t = self::id() ?? 0;

        return DB::table('orden_trabajo_tecnico')->where('tecnico_id', $t)->select('orden_trabajo_id')
            ->union(DB::table('ordenes_trabajo')->where('tecnico_id', $t)->select('id'));
    }
}
