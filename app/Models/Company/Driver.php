<?php

namespace App\Models\Company;

use App\Models\DriverLicenseType;
use App\Models\DynamicModel;
use App\Traits\HasFullName;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Driver extends DynamicModel
{
    use HasFullName;

    protected string $companyTableKey = 'drivers';

    protected array $defaultRelations = [
        'licenseType',
        'defaultAccount',
    ];

    public function licenseType(): BelongsTo
    {
        return $this->belongsTo(DriverLicenseType::class, 'license_type');
    }

    public function accounts(): HasMany
    {
        return $this->hasManyCompany(DriverAccount::class, 'driver_id');
    }

    public function defaultAccount(): HasOne
    {
        return $this->accounts()
            ->one()
            ->where('is_default', true);
    }
}
