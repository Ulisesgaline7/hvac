<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Resuelve el técnico (tabla tecnicos) del usuario con sesión. */
final class TecnicoActual
{
    private static ?int $id = null;
    private static bool $resuelto = false;

    public static function id(): ?int
    {
        if (self::$resuelto) {
            return self::$id;
        }
        self::$resuelto = true;
        $u = auth()->user();
        if (! $u) {
            return null;
        }

        $q = DB::table('tecnicos');
        if (Schema::hasColumn('tecnicos', 'user_id')) {
            $q->where('user_id', $u->id);
        } elseif (Schema::hasColumn('tecnicos', 'email')) {
            $q->where('email', $u->email);
        } else {
            return null;
        }

        return self::$id = ($q->value('id') ? (int) $q->value('id') : null);
    }

    /** IDs de OT donde participa (líder, equipo o supervisor). */
    public static function otIds()
    {
        $t = self::id() ?? 0;

        return DB::table('orden_trabajo_tecnico')->where('tecnico_id', $t)->select('orden_trabajo_id')
            ->union(DB::table('ordenes_trabajo')->where('tecnico_id', $t)->select('id'));
    }
}
