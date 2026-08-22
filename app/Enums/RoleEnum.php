<?php

namespace App\Enums;

enum RoleEnum: string
{
    case SUPERADMIN = 'superAdmin';
    case ADMIN = 'admin';
    case USER = 'user';
}
