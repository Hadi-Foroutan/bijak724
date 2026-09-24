<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum WaybillStatus: string
{
    use EnumHelpers;

    case Incomplete = 'incomplete';
    case Completed = 'completed';
    case Referral = 'referral';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Incomplete => __('public.waybill_status_incomplete'),
            self::Completed => __('public.waybill_status_completed'),
            self::Referral => __('public.waybill_status_referral'),
            self::Canceled => __('public.waybill_status_canceled'),
        };
    }
}
