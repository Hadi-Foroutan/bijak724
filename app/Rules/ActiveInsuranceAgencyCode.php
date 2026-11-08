<?php

namespace App\Rules;

use App\Enums\StatusEnum;
use App\Models\InsuranceAgency;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** The code of an active, not deleted agency of the insurer with this `org_code` (agency codes are only unique per insurer). */
class ActiveInsuranceAgencyCode implements ValidationRule
{
    public function __construct(protected mixed $insuranceCompanyOrgCode)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // A missing or malformed org_code is reported on its own field.
        if (!is_numeric($this->insuranceCompanyOrgCode)) {
            return;
        }

        $exists = InsuranceAgency::query()
            ->where('code', (string)$value)
            ->where('status', StatusEnum::ACTIVE->value)
            ->whereHas('insuranceCompany', fn($query) => $query->where('org_code', (int)$this->insuranceCompanyOrgCode))
            ->exists();

        if (!$exists) {
            $fail(__('public.insurance_agency_not_found'));
        }
    }
}
