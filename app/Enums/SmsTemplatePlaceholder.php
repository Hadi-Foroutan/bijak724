<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

/** The keys a waybill SMS template may contain, written as `{key}`; SmsTemplateRenderer fills them in. */
enum SmsTemplatePlaceholder: string
{
    use EnumHelpers;

    case BijakNumber = 'bijak_number';
    case TrackingCode = 'tracking_code';
    case ReferralNumber = 'referral_number';
    case IssuedAt = 'issued_at';
    case SenderFullName = 'sender_full_name';
    case ReceiverFullName = 'receiver_full_name';
    case DriverFullName = 'driver_full_name';
    case OriginCity = 'origin_city';
    case DestinationCity = 'destination_city';
    case CompanyName = 'company_name';

    public function label(): string
    {
        return match ($this) {
            self::BijakNumber => 'شماره بیجک',
            self::TrackingCode => 'کد رهگیری بیجک',
            self::ReferralNumber => 'شماره حواله',
            self::IssuedAt => 'تاریخ صدور',
            self::SenderFullName => 'نام فرستنده',
            self::ReceiverFullName => 'نام گیرنده',
            self::DriverFullName => 'نام راننده',
            self::OriginCity => 'شهر مبدأ',
            self::DestinationCity => 'شهر مقصد',
            self::CompanyName => 'نام شرکت',
        };
    }

    public function token(): string
    {
        return '{' . $this->value . '}';
    }

    /** @return list<string> the keys of $body, known or not */
    public static function keysIn(string $body): array
    {
        preg_match_all('/\{([^{}]*)\}/u', $body, $matches);

        return array_values(array_unique($matches[1]));
    }
}
