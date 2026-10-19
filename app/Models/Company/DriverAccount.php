<?php

namespace App\Models\Company;

use App\Models\DynamicModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverAccount extends DynamicModel
{
    protected string $companyTableKey = 'driver_accounts';

    protected $fillable = [
        'owner_company_id',
        'driver_id',
        'sheba_number',
        'bank_name',
        'owner_name',
        'is_default',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsToCompany(Driver::class, 'driver_id', relationName: 'driver');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }
}
