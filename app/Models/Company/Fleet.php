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

    protected $fillable = [
        'owner_company_id',
        'smart_card_number',
        'status',
        'ownership_type',
        'plate_first_number',
        'plate_second_letter',
        'plate_third_number',
        'plate_fourth_number',
        'manufacture_year',
        'driver_license_type_id',
        'owner_mobile',
        'loading_type_id',
        'insurance_policy_number',
        'chassis_number',
        'engine_number',
        'vin',
        'system_id',
        'tip_code',
        'document_date',
        'document_number',
        'insurance_date',
        'technical_inspection_valid_until',
        'has_violation',
        'description',
    ];

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
