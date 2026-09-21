<?php

namespace App\Repositories;

use App\Interfaces\InsuranceCompanyRepositoryInterface;
use App\Models\InsuranceCompany;

class InsuranceCompanyRepository implements InsuranceCompanyRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function findOrCreateByOrganizationCode(int $organizationCode, array $data): InsuranceCompany
    {
        $insuranceCompany = InsuranceCompany::query()
            ->where('org_code', $organizationCode)
            ->first();

        if ($insuranceCompany !== null) {
            return $insuranceCompany;
        }

        return InsuranceCompany::query()->forceCreate([
            ...$data,
            'org_code' => $organizationCode,
        ]);
    }
}
