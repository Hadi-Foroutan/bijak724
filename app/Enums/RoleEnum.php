<?php

namespace App\Enums;

enum RoleEnum: string
{
    case SUPERADMIN = 'superAdmin';
    case ADMIN = 'admin';
    case COMPANY_MANAGER = 'companyManager';
    case USER = 'user';
}
