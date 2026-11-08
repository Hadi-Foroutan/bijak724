<?php

namespace App\Repositories;

use App\Enums\InsuranceLevel;
use App\Enums\StatusEnum;
use App\Interfaces\InsurancePanelRepositoryInterface;
use App\Models\Company;
use App\Models\Company\Waybill;
use App\Models\Insurance;
use App\Models\InsuranceAgency;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InsurancePanelRepository implements InsurancePanelRepositoryInterface
{
    public function companies(InsuranceAgency $scope, array $filters): Collection|LengthAwarePaginator
    {
        $counts = $this->visibleInsurances($scope)
            ->selectRaw('company_id, COUNT(*) as insurances_count')
            ->groupBy('company_id')
            ->pluck('insurances_count', 'company_id');

        $companies = Company::searchRecords(
            $filters,
            fn(Builder $query): Builder => $query->whereIn('id', $counts->keys()->all())->orderBy('name'),
        );

        ($companies instanceof LengthAwarePaginator ? $companies->getCollection() : $companies)
            ->each(fn(Company $company) => $company->setAttribute('insurances_count', (int)($counts[$company->getKey()] ?? 0)));

        return $companies;
    }

    public function insuranceIds(InsuranceAgency $scope, int $companyId): array
    {
        return $this->visibleInsurances($scope)
            ->where('company_id', $companyId)
            ->pluck('id')
            ->map(fn(mixed $id): int => (int)$id)
            ->all();
    }

    public function findCompany(int $companyId): ?Company
    {
        return Company::query()->find($companyId);
    }

    public function waybills(int $companyId, array $insuranceIds, array $filters): Collection|LengthAwarePaginator
    {
        return Waybill::searchRecordsForCompany(
            $companyId,
            $filters,
            fn(Builder $query): Builder => $query->whereIn('liability_insurance', $insuranceIds),
        );
    }

    public function findWaybill(int $companyId, array $insuranceIds, int $id): Waybill
    {
        return Waybill::findForCompanyOrFail(
            $companyId,
            $id,
            fn(Builder $query): Builder => $query->whereIn('liability_insurance', $insuranceIds),
        );
    }

    /** @return Builder<Insurance> the contracts of the scope's level, without the contracts the admin cut off from their agency */
    private function visibleInsurances(InsuranceAgency $scope): Builder
    {
        $query = Insurance::query()->where('insurance_company_id', $scope->insurance_company_id);

        match ($scope->level) {
            InsuranceLevel::Country => null,
            InsuranceLevel::Province => $query->whereIn('insurance_agency_id', $this->agenciesOfProvince($scope)),
            InsuranceLevel::Agency => $query->where('insurance_agency_id', $scope->getKey()),
        };

        return $query->where(fn(Builder $query) => $query
            ->whereNull('insurance_agency_id')
            ->orWhere('insurance_agency_status', StatusEnum::ACTIVE->value));
    }

    /** @return Builder<InsuranceAgency> the province agent itself and the agencies under it */
    private function agenciesOfProvince(InsuranceAgency $scope): Builder
    {
        return InsuranceAgency::query()
            ->select('id')
            ->where(fn(Builder $query) => $query->whereKey($scope->getKey())->orWhere('parent_id', $scope->getKey()));
    }
}
