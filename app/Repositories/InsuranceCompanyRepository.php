<?php

namespace App\Repositories;

use App\Interfaces\InsuranceCompanyRepositoryInterface;
use App\Models\InsuranceCompany;

class InsuranceCompanyRepository implements InsuranceCompanyRepositoryInterface
{
    public function findByOrganizationCode(int $organizationCode): ?InsuranceCompany
    {
        return InsuranceCompany::query()->where('org_code', $organizationCode)->first();
    }

    /** @param array<string, mixed> $data */
    public function findOrCreateByOrganizationCode(int $organizationCode, array $data): InsuranceCompany
    {
        $insuranceCompany = $this->findByOrganizationCode($organizationCode);

        if ($insuranceCompany !== null) {
            return $insuranceCompany;
        }

        return InsuranceCompany::query()->forceCreate([
            ...$data,
            'org_code' => $organizationCode,
        ]);
    }
}
