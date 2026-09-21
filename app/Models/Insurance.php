<?php

namespace App\Models;

use App\Enums\StatusEnum;
use App\Traits\AdvancedSearch;
use Database\Factories\InsuranceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Insurance extends Model
{
    /** @use HasFactory<InsuranceFactory> */
    /** @use HasFactory<InsuranceFactory> */
    use AdvancedSearch, HasFactory;

    protected array $searchableFields = [
        'company_id', 'insurance_company_id', 'title', 'contract_number', 'is_default',
        'status', 'start_date', 'end_date', 'representative_first_name',
        'representative_last_name', 'representative_mobile', 'representative_phone',
        'representative_email', 'insuranceCompany__name',
    ];

    protected array $globalSearchFields = [
        'title', 'contract_number', 'description', 'representative_first_name',
        'representative_last_name', 'representative_mobile', 'representative_phone',
        'representative_email', 'representative_address',
    ];

    protected $fillable = [
        'company_id', 'insurance_company_id', 'title', 'contract_number', 'is_default',
        'status', 'start_date', 'end_date', 'description', 'representative_first_name',
        'representative_last_name', 'representative_mobile', 'representative_phone',
        'representative_fax', 'representative_email', 'representative_address',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'status' => StatusEnum::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function tariffs(): HasMany
    {
        return $this->hasMany(InsuranceTariff::class)->orderBy('cargo_value_from');
    }
}
