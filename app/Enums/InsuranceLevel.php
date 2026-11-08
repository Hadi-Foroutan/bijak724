<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

/** The level of an insurance agent in its insurer's tree, which decides how much of the insurer's waybills its users see. */
enum InsuranceLevel: string
{
    use EnumHelpers;

    /** Every waybill insured by the insurer (one country agent per insurer). */
    case Country = 'country';
    /** The waybills insured through the province agent and the agencies under it (one province agent per state). */
    case Province = 'province';
    /** Only the waybills insured through the user's own agency. */
    case Agency = 'agency';

    public function label(): string
    {
        return match ($this) {
            self::Country => 'کشوری',
            self::Province => 'استانی',
            self::Agency => 'نمایندگی',
        };
    }
}
