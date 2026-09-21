<?php

namespace App\Models\Company;

use App\Models\DriverLicenseType;
use App\Models\DynamicModel;
use App\Models\FleetBrand;
use App\Models\FleetType;
use App\Models\LoadingType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fleet extends DynamicModel
{
    protected string $companyTableKey = 'fleets';

    protected array $defaultRelations = [
        'driverLicenseType',
        'loadingType',
        'fleetBrand',
        'fleetType',
    ];

    public function driverLicenseType(): BelongsTo
    {
        return $this->belongsTo(DriverLicenseType::class, 'driver_license_type_id');
    }

    public function loadingType(): BelongsTo
    {
        return $this->belongsTo(LoadingType::class, 'loading_type_id');
    }

    public function fleetBrand(): BelongsTo
    {
        return $this->belongsTo(FleetBrand::class, 'system_id');
    }

    public function fleetType(): BelongsTo
    {
        return $this->belongsTo(FleetType::class, 'tip_code', 'tip_code');
    }
}
