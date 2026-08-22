<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum UserStatusEnum: string
{
    use EnumHelpers;
    case ACTIVE = "active";
    case INACTIVE = "inactive";
    case BLOCKED = "blocked";
}
