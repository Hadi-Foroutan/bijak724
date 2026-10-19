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

    protected $fillable = [
        'owner_company_id',
        'national_code',
        'first_name',
        'last_name',
        'full_name',
        'father_name',
        'license_number',
        'license_type',
        'license_expiry_date',
        'phone_number_1',
        'phone_number_2',
        'phone_number_3',
        'profile_image_path',
        'description',
        'status',
    ];

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
