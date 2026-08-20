<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum CompanyParentEnum: string
{
    use EnumHelpers;
    case ORIGINAL = "original";
    case BRANCH = "branch";
}
