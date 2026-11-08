<?php

namespace App\Enums;

enum RoleEnum: string
{
    case SUPERADMIN = 'superAdmin';
    case ADMIN = 'admin';
    case COMPANY_MANAGER = 'companyManager';
    case USER = 'user';
    case DEMO = 'demo';
    case INSURANCE = 'insurance';

    /** The roles of the panels that are not a company's: their users belong to no company. */
    public static function withoutCompany(): array
    {
        return [self::ADMIN->value, self::SUPERADMIN->value, self::INSURANCE->value];
    }
}
