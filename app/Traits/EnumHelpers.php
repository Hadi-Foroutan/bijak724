<?php

namespace App\Traits;

trait EnumHelpers
{
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'name' => $case->name,
            'value' => $case->value,
        ], self::cases());
    }
}
