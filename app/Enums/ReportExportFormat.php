<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum ReportExportFormat: string
{
    use EnumHelpers;

    case Xlsx = 'xlsx';
    case Pdf = 'pdf';

    public function mimeType(): string
    {
        return match ($this) {
            self::Xlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::Pdf => 'application/pdf',
        };
    }
}
