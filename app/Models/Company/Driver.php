<?php

namespace App\Models\Company;

use App\Models\DriverLicenseType;
use App\Models\DynamicModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Driver extends DynamicModel
{
    protected string $companyTableKey = 'drivers';

    protected array $defaultRelations = [
        'licenseType',
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
}
