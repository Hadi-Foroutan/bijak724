<?php

namespace App\Interfaces;

use App\Models\Company;
use App\Models\Insurance;
use App\Models\InsuranceAgency;
use App\Models\InsuranceCompany;
use App\Models\State;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/** The insurance agents (country, province and agency levels), and the companies connected to them. */
interface InsuranceAgencyRepositoryInterface
{
    /** @return Collection<int, InsuranceAgency>|LengthAwarePaginator */
    public function search(array $filters): Collection|LengthAwarePaginator;

    /** @return EloquentCollection<int, InsuranceAgency> the matching agents as a tree (country → province → agency) */
    public function tree(array $filters): EloquentCollection;

    public function findOrFail(int $id): InsuranceAgency;

    /** An agency that is not deleted and is active, by its code and its insurer's `org_code`; null when there is none. */
    public function findActiveByCode(int $insuranceCompanyOrgCode, string $code): ?InsuranceAgency;

    /** @param array<string, mixed> $data */
    public function create(array $data): InsuranceAgency;

    /** @param array<string, mixed> $data */
    public function update(InsuranceAgency $agency, array $data): InsuranceAgency;

    public function delete(InsuranceAgency $agency): void;

    /** @return EloquentCollection<int, InsuranceCompany> every insurer */
    public function insuranceCompanies(): EloquentCollection;

    /** @return EloquentCollection<int, State> every state (province) */
    public function states(): EloquentCollection;

    /** The insurer's country agent: created (legal, named "شرکت {insurer}") when it has none, so an insurer never has two. */
    public function ensureCountry(InsuranceCompany $insuranceCompany): InsuranceAgency;

    /** The province agent of the state under the country agent: created (legal, named "سرپرستی استان {state}") when there is none. */
    public function ensureProvince(InsuranceAgency $country, State $state): InsuranceAgency;

    /**
     * The contracts connected to the agency (with their company), connected or cut off.
     *
     * @return Collection<int, Insurance>
     */
    public function connectedInsurances(InsuranceAgency $agency): Collection;

    /**
     * The contracts of the company with the agency's insurer: the ones the agency can be connected to.
     *
     * @return Collection<int, Insurance>
     */
    public function companyInsurances(InsuranceAgency $agency, int $companyId): Collection;

    /**
     * The companies with a contract connected to the agency (connected or cut off), each with how many contracts.
     *
     * @return Collection<int, array{company: Company, insurances_count: int, active_count: int}>
     */
    public function connectedCompanies(InsuranceAgency $agency): Collection;

    /**
     * The companies not connected to the agency yet: none of their contracts is the agency's. `$search` matches the
     * name or the organization code.
     *
     * @return Collection<int, Company>
     */
    public function unconnectedCompanies(InsuranceAgency $agency, ?string $search = null): Collection;

    public function findInsuranceOrFail(int $id): Insurance;

    /** Connects the contract to the agency, or turns a cut connection on again. */
    public function connectInsurance(InsuranceAgency $agency, Insurance $insurance): void;

    /** Cuts the connection; the contract keeps its agency so the admin can turn it on again. */
    public function disconnectInsurance(Insurance $insurance): void;
}
