<?php

namespace App\Models\Company;

use App\Models\DynamicModel;

class
CanceledReferral extends DynamicModel
{
    protected string $companyTableKey = 'canceled_referrals';

    protected $fillable = [
        'owner_company_id',
        'waybill_id',
        'canceled_by',
        'canceled_by_print_name',
        'referral_number',
        'referral_serial',
        'waybill_snapshot',
        'cargos_snapshot',
        'canceled_at',
        'reason',
    ];

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
