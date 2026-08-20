<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum StatusEnum: string
{
    use EnumHelpers;
    case ACTIVE = "active";
    case INACTIVE = "inactive";
}
