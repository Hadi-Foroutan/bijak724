<?php

namespace App\Services\Company\Sms;

use App\Enums\SmsTemplatePlaceholder;
use App\Models\City;
use App\Models\Company;
use App\Models\Company\Waybill;
use App\Support\JalaliDate;

class SmsTemplateRenderer
{
    /** @return array<string, ?string> every placeholder key and its value on this waybill */
    public function values(Waybill $waybill, Company $company): array
    {
        return [
            SmsTemplatePlaceholder::BijakNumber->value => $waybill->bijak_number,
            SmsTemplatePlaceholder::TrackingCode->value => $waybill->bijak_tracking_code,
            SmsTemplatePlaceholder::ReferralNumber->value => $waybill->referral_number,
            SmsTemplatePlaceholder::IssuedAt->value => $waybill->issued_at === null ? null : JalaliDate::date($waybill->issued_at),
            SmsTemplatePlaceholder::SenderFullName->value => $waybill->sender_full_name,
            SmsTemplatePlaceholder::ReceiverFullName->value => $waybill->receiver_full_name,
            SmsTemplatePlaceholder::DriverFullName->value => $waybill->driver1_full_name
                ?? $waybill->referral_driver_full_name
                ?? $waybill->driver2_full_name,
            SmsTemplatePlaceholder::OriginCity->value => $this->cityName($waybill->sender_address_city_code),
            SmsTemplatePlaceholder::DestinationCity->value => $this->cityName($waybill->receiver_address_city_code),
            SmsTemplatePlaceholder::CompanyName->value => $company->name,
        ];
    }

    /**
     * @param array<string, ?string> $values
     * @return list<string> the placeholders of $body this waybill has no value for
     */
    public function missingKeys(string $body, array $values): array
    {
        return array_values(array_filter(
            SmsTemplatePlaceholder::keysIn($body),
            fn(string $key): bool => blank($values[$key] ?? null),
        ));
    }

    /** @param array<string, ?string> $values */
    public function render(string $body, array $values): string
    {
        $replacements = [];

        foreach ($values as $key => $value) {
            $replacements['{' . $key . '}'] = (string)$value;
        }

        return strtr($body, $replacements);
    }

    private function cityName(int|string|null $code): ?string
    {
        return $code === null ? null : City::query()->where('code', $code)->value('name');
    }
}
