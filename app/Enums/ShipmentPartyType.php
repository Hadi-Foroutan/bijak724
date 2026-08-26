<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum ShipmentPartyType: string
{
    use EnumHelpers;

    case Sender = 'sender';
    case Receiver = 'receiver';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Sender => 'فرستنده',
            self::Receiver => 'گیرنده',
            self::Both => 'فرستنده و گیرنده',
        };
    }
}
