<?php

namespace App\Enums;

enum WaybillStatus: string
{
    case Incomplete = 'incomplete';
    case Issued = 'issued';

    public static function fromIncomplete(bool $isIncomplete): self
    {
        return $isIncomplete ? self::Incomplete : self::Issued;
    }

    public function label(): string
    {
        return match ($this) {
            self::Incomplete => __('public.waybill_status_incomplete'),
            self::Issued => __('public.waybill_status_issued'),
        };
    }
}
