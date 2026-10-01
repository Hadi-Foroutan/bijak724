<?php

namespace App\Enums;

enum SettingValueType: string
{
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case String = 'string';

    public function cast(?string $value): bool|int|float|string|null
    {
        if ($value === null) {
            return null;
        }

        return match ($this) {
            self::Boolean => in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true),
            self::Integer => (int) $value,
            self::Decimal => (float) $value,
            self::String => $value,
        };
    }

    public function serialize(bool|int|float|string|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($this) {
            self::Boolean => (bool) $value ? '1' : '0',
            self::Integer => (string) ((int) $value),
            self::Decimal => (string) ((float) $value),
            self::String => (string) $value,
        };
    }

    public function validationRule(): string
    {
        return match ($this) {
            self::Boolean => 'boolean',
            self::Integer => 'integer',
            self::Decimal => 'numeric',
            self::String => 'string',
        };
    }
}
