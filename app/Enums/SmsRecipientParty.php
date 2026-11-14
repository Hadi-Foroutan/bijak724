<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

/** The parties of a waybill that receive an SMS; each has its own template. */
enum SmsRecipientParty: string
{
    use EnumHelpers;

    case Sender = 'sender';
    case Receiver = 'receiver';
    case Driver = 'driver';

    public function label(): string
    {
        return match ($this) {
            self::Sender => 'فرستنده',
            self::Receiver => 'گیرنده',
            self::Driver => 'راننده',
        };
    }
}
