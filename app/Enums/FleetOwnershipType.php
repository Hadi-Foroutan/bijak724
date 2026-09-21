<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum FleetOwnershipType: string
{
    use EnumHelpers;

    case Owned = 'owned';
    case Leased = 'leased';
    case Free = 'free';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Owned => 'ملکی',
            self::Leased => 'استیجاری',
            self::Free => 'آزاد',
            self::Unknown => 'نامشخص',
        };
    }
}
