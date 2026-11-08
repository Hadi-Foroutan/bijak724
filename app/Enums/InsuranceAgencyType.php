<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

/** Whether an insurance agent is a person (name and national code) or a company (name and company id code). */
enum InsuranceAgencyType: string
{
    use EnumHelpers;

    /** حقیقی: first name, last name and national code. */
    case Natural = 'natural';
    /** حقوقی: company name and company id code. Country agents are always legal. */
    case Legal = 'legal';

    public function label(): string
    {
        return match ($this) {
            self::Natural => 'حقیقی',
            self::Legal => 'حقوقی',
        };
    }
}
