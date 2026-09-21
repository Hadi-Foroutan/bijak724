<?php

namespace App\Enums;

enum NotificationType: string
{
    case Success = 'success';
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';

    public function label(): string
    {
        return match ($this) {
            self::Success => __('public.notification_type_success'),
            self::Error => __('public.notification_type_error'),
            self::Warning => __('public.notification_type_warning'),
            self::Info => __('public.notification_type_info'),
        };
    }
}
