<?php

namespace App\Support\Excel;

use BackedEnum;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Stringable;
use UnitEnum;

/**
 * The single place that decides how any PHP value is written in a cell, so every export handles fields the same way:
 * numbers stay numbers, everything else is text.
 */
class ExcelValue
{
    public const LIST_SEPARATOR = '، ';

    public static function normalize(mixed $value): int|float|string
    {
        return match (true) {
            $value === null => '',
            is_int($value), is_float($value) => $value,
            is_bool($value) => $value ? 'بله' : 'خیر',
            $value instanceof BackedEnum => method_exists($value, 'label') ? (string)$value->label() : (string)$value->value,
            $value instanceof UnitEnum => $value->name,
            $value instanceof CarbonInterface, $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
            is_array($value) => self::fromArray($value),
            $value instanceof Arrayable => self::fromArray($value->toArray()),
            $value instanceof JsonSerializable => self::fromArray((array)$value->jsonSerialize()),
            is_scalar($value), $value instanceof Stringable => (string)$value,
            default => '',
        };
    }

    /** @param array<int|string, mixed> $value */
    private static function fromArray(array $value): string
    {
        if (array_is_list($value)) {
            return implode(self::LIST_SEPARATOR, array_filter(
                array_map(fn(mixed $item): string => (string)self::normalize($item), $value),
                fn(string $item): bool => $item !== '',
            ));
        }

        return (string)json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
