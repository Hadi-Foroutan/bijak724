<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum AdditionalFieldType: string
{
    use EnumHelpers;

    case Text = 'text';
    case Number = 'number';
    case DateTime = 'date_time';
    case Selection = 'selection';
    case Date = 'date';
    case Time = 'time';
    case LongText = 'long_text';
    case Checkbox = 'checkbox';
    case Radio = 'radio';
    case File = 'file';
    case Image = 'image';
    case Email = 'email';
    case Url = 'url';

    /**
     * Rule a value submitted for a field of this type must pass.
     */
    public function valueRule(): string
    {
        return match ($this) {
            self::Number => 'numeric',
            self::Date, self::DateTime => 'date',
            self::Time => 'date_format:H:i,H:i:s',
            self::Checkbox => 'boolean',
            self::Email => 'email',
            self::Url => 'url',
            default => 'string',
        };
    }
}
