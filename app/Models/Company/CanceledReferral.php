<?php

namespace App\Models\Company;

use App\Models\DynamicModel;

class CanceledReferral extends DynamicModel
{
    protected string $companyTableKey = 'canceled_referrals';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'waybill_snapshot' => 'array',
            'cargos_snapshot' => 'array',
            'canceled_at' => 'datetime',
        ];
    }
}
