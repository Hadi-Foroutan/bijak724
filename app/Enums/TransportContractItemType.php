<?php

namespace App\Enums;

enum TransportContractItemType: string
{
    case Owned = 'owned';
    case Rental = 'rental';
    case Free = 'free';
    case Unknown = 'unknown';

    public function field(): string
    {
        return 'is_'.$this->value;
    }

    public function defaultField(): string
    {
        return 'default_'.$this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::Owned => 'ملکی', self::Rental => 'استیجاری',
            self::Free => 'آزاد', self::Unknown => 'نامشخص',
        };
    }

    public function defaultLabel(): string
    {
        return 'پیش‌فرض '.$this->label();
    }
}
