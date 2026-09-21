<?php

namespace App\Models\Company;

use App\Models\DriverLicenseType;
use App\Models\DynamicModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Driver extends DynamicModel
{
    protected string $companyTableKey = 'drivers';

    protected array $defaultRelations = [
        'licenseType',
        'defaultAccount',
    ];

    protected static function booted(): void
    {
        static::saving(function (Driver $driver): void {
            if ($driver->first_name === null || $driver->last_name === null) {
                return;
            }

            $driver->full_name = Str::squish("{$driver->first_name} {$driver->last_name}");
        });
    }

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
