<?php

namespace App\Enums;

/** Settings shared by the whole system and managed by admins (table `settings`). */
enum GlobalSettingKey: string
{
    case ReportRetentionHours = 'reports.retention_hours';
    case SmsPanelUrl = 'sms.panel_url';
    case SiteStatus = 'site.status';

    public function valueType(): SettingValueType
    {
        return match ($this) {
            self::ReportRetentionHours => SettingValueType::Integer,
            self::SmsPanelUrl, self::SiteStatus => SettingValueType::String,
        };
    }

    public function defaultValue(): bool|int|float|string|null
    {
        return match ($this) {
            self::ReportRetentionHours => 8,
            self::SmsPanelUrl => config('sms.drivers.panel.base_url'),
            self::SiteStatus => SiteStatus::Active->value,
        };
    }

    /** @return list<string> */
    public function rules(): array
    {
        return match ($this) {
            self::ReportRetentionHours => ['integer', 'min:1', 'max:720'],
            self::SmsPanelUrl => ['string', 'url:http,https', 'max:255'],
            self::SiteStatus => ['string', 'in:' . implode(',', SiteStatus::values())],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ReportRetentionHours => 'مدت نگهداری فایل گزارش‌ها (ساعت)',
            self::SmsPanelUrl => 'آدرس سامانه پیامک',
            self::SiteStatus => 'وضعیت سایت',
        };
    }
}
