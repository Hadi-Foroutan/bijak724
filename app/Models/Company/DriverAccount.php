<?php

namespace App\Models\Company;

use App\Models\DynamicModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverAccount extends DynamicModel
{
    protected string $companyTableKey = 'driver_accounts';

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
