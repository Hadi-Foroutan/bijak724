<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum ReferralNumberStatus: string
{
    use EnumHelpers;

    case Active = 'active';
    case Inactive = 'inactive';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'فعال',
            self::Inactive => 'غیرفعال',
            self::Completed => 'اتمام‌شده',
        };
    }
}
