<?php

namespace App\Models\Company;

use App\Models\DynamicModel;

class ReferralNumber extends DynamicModel
{
    protected string $companyTableKey = 'referral_numbers';

    protected $fillable = [
        'owner_company_id',
        'title',
        'serial_number',
        'from_number',
        'to_number',
        'last_number',
        'status',
        'active_slot',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'from_number' => 'integer',
            'to_number' => 'integer',
            'last_number' => 'integer',
            'active_slot' => 'integer',
        ];
    }
}
