<?php

namespace App\Services\Company;

use App\Enums\CompanyParentEnum;
use App\Models\Company;

class CompanyHierarchyService
{
    /** @var array<int, list<int>> */
    private array $visibleUserCompanyIds = [];

    /** @return list<int> */
    public function visibleUserCompanyIds(int $companyId): array
    {
        if (isset($this->visibleUserCompanyIds[$companyId])) {
            return $this->visibleUserCompanyIds[$companyId];
        }

        $company = Company::query()
            ->select(['id', 'parent_id', 'parent_type'])
            ->findOrFail($companyId);

        if ($company->parent_id !== null
            || $company->parent_type !== CompanyParentEnum::ORIGINAL->value) {
            return $this->visibleUserCompanyIds[$companyId] = [$companyId];
        }

        $companyIds = [$companyId];
        $pendingParentIds = [$companyId];

        while ($pendingParentIds !== []) {
            $childCompanyIds = Company::query()
                ->whereIn('parent_id', $pendingParentIds)
                ->whereNotIn('id', $companyIds)
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            if ($childCompanyIds === []) {
                break;
            }

            $companyIds = [...$companyIds, ...$childCompanyIds];
            $pendingParentIds = $childCompanyIds;
        }

        return $this->visibleUserCompanyIds[$companyId] = $companyIds;
    }
}
