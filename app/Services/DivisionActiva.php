<?php

namespace App\Services;

class DivisionActiva
{
    private static ?int $id = null;

    public static function id(): ?int
    {
        return self::$id;
    }

    public static function fijar(?int $id): void
    {
        self::$id = $id;
    }
}
