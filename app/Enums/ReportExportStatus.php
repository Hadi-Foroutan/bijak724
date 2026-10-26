<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum ReportExportStatus: string
{
    use EnumHelpers;

    case Queued = 'queued';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'در صف',
            self::Processing => 'در حال آماده‌سازی',
            self::Completed => 'تکمیل شده',
            self::Failed => 'ناموفق',
            self::Expired => 'منقضی شده',
        };
    }
}
