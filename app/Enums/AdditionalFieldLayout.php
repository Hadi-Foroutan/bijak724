<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum AdditionalFieldLayout: string
{
    use EnumHelpers;

    case Rtl = 'rtl';
    case Ltr = 'ltr';
}
