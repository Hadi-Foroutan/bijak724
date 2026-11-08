<?php

namespace App\Interfaces;

use App\Models\Company;
use App\Models\Company\Waybill;
use App\Models\InsuranceAgency;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * What a user of the insurance panel may see, by the level of the user's agency (the `$scope`):
 *  - country: every contract of the agency's insurer;
 *  - province: the contracts recorded with the province agent itself or with the agencies under it;
 *  - agency: only the contracts recorded with the agency itself.
 * A contract whose company was cut off from its agency by the admin is never seen.
 */
interface InsurancePanelRepositoryInterface
{
    /**
     * The companies that have contracts the user may see; each has an `insurances_count` attribute.
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, Company>|LengthAwarePaginator
     */
    public function companies(InsuranceAgency $scope, array $filters): Collection|LengthAwarePaginator;

    /** @return list<int> the ids of the company's contracts the user may see (empty = the company is not visible to the user) */
    public function insuranceIds(InsuranceAgency $scope, int $companyId): array;

    public function findCompany(int $companyId): ?Company;

    /**
     * The waybills of the company that were insured by those contracts.
     *
     * @param list<int> $insuranceIds
     * @param array<string, mixed> $filters
     * @return Collection<int, Waybill>|LengthAwarePaginator
     */
    public function waybills(int $companyId, array $insuranceIds, array $filters): Collection|LengthAwarePaginator;

    /** @param list<int> $insuranceIds */
    public function findWaybill(int $companyId, array $insuranceIds, int $id): Waybill;
}
