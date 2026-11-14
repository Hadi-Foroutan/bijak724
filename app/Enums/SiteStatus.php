<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum SiteStatus: string
{
    use EnumHelpers;

    case Active = 'active';
    case Maintenance = 'maintenance';
}
