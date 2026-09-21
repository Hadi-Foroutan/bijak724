<?php

namespace App\Interfaces;

use App\Models\InsuranceCompany;

interface InsuranceCompanyRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function findOrCreateByOrganizationCode(int $organizationCode, array $data): InsuranceCompany;
}
