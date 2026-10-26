<?php

namespace App\Enums;

/** Settings shared by the whole system and managed by admins (table `settings`). */
enum GlobalSettingKey: string
{
    case ReportRetentionHours = 'reports.retention_hours';

    public function valueType(): SettingValueType
    {
        return match ($this) {
            self::ReportRetentionHours => SettingValueType::Integer,
        };
    }

    public function defaultValue(): bool|int|float|string|null
    {
        return match ($this) {
            self::ReportRetentionHours => 8,
        };
    }

    /** @return list<string> */
    public function rules(): array
    {
        return match ($this) {
            self::ReportRetentionHours => ['integer', 'min:1', 'max:720'],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ReportRetentionHours => 'مدت نگهداری فایل گزارش‌ها (ساعت)',
        };
    }
}
