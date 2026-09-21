<?php

namespace App\Services\Company;

use App\Models\Company;
use LogicException;

class CompanyDataOwnerResolver
{
    /** @var array<int, int> */
    private array $resolvedCompanyIds = [];

    public function resolveId(int $companyId): int
    {
        if (isset($this->resolvedCompanyIds[$companyId])) {
            return $this->resolvedCompanyIds[$companyId];
        }

        $company = Company::query()
            ->select(['id', 'parent_id'])
            ->find($companyId);

        if (! $company instanceof Company) {
            return $companyId;
        }

        $visitedCompanyIds = [];

        while ($company->parent_id !== null) {
            if (isset($visitedCompanyIds[$company->id])) {
                throw new LogicException("Circular company hierarchy detected for company [{$companyId}].");
            }

            $visitedCompanyIds[$company->id] = true;
            $company = Company::query()
                ->select(['id', 'parent_id'])
                ->findOrFail($company->parent_id);
        }

        $dataOwnerCompanyId = (int) $company->getKey();

        foreach (array_keys($visitedCompanyIds) as $visitedCompanyId) {
            $this->resolvedCompanyIds[$visitedCompanyId] = $dataOwnerCompanyId;
        }

        return $this->resolvedCompanyIds[$companyId] = $dataOwnerCompanyId;
    }

    public function isDataOwner(int $companyId): bool
    {
        return $this->resolveId($companyId) === $companyId;
    }
}
