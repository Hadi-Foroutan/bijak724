<?php

namespace App\Repositories;

use App\Enums\InsuranceAgencyType;
use App\Enums\InsuranceLevel;
use App\Enums\StatusEnum;
use App\Interfaces\InsuranceAgencyRepositoryInterface;
use App\Models\Company;
use App\Models\Insurance;
use App\Models\InsuranceAgency;
use App\Models\InsuranceCompany;
use App\Models\State;
use App\Services\TreeBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class InsuranceAgencyRepository implements InsuranceAgencyRepositoryInterface
{
    private const RELATIONS = ['insuranceCompany', 'state', 'city'];

    public function __construct(protected TreeBuilder $treeBuilder)
    {
    }

    public function search(array $filters): Collection|LengthAwarePaginator
    {
        return InsuranceAgency::searchRecords(
            $filters,
            fn($query) => $query->with(self::RELATIONS)->orderByDesc('id'),
        );
    }

    public function tree(array $filters): EloquentCollection
    {
        unset($filters['tree'], $filters['paginate']);

        /** @var EloquentCollection<int, InsuranceAgency> $agencies */
        $agencies = InsuranceAgency::searchRecords(
            $filters,
            fn($query) => $query->with(self::RELATIONS)->reorder()->orderBy('id'),
        );

        return $this->treeBuilder->build($agencies);
    }

    public function findOrFail(int $id): InsuranceAgency
    {
        return InsuranceAgency::query()->with(self::RELATIONS)->findOrFail($id);
    }

    public function findActiveByCode(int $insuranceCompanyOrgCode, string $code): ?InsuranceAgency
    {
        return InsuranceAgency::query()
            ->with('insuranceCompany')
            ->where('code', $code)
            ->where('status', StatusEnum::ACTIVE->value)
            ->whereHas('insuranceCompany', fn($query) => $query->where('org_code', $insuranceCompanyOrgCode))
            ->first();
    }

    public function create(array $data): InsuranceAgency
    {
        return InsuranceAgency::query()->create($data)->load(self::RELATIONS);
    }

    public function update(InsuranceAgency $agency, array $data): InsuranceAgency
    {
        $agency->update($data);

        return $agency->refresh()->load(self::RELATIONS);
    }

    public function delete(InsuranceAgency $agency): void
    {
        $agency->delete();
    }

    public function insuranceCompanies(): EloquentCollection
    {
        return InsuranceCompany::query()->orderBy('id')->get();
    }

    public function states(): EloquentCollection
    {
        return State::query()->orderBy('code')->get();
    }

    public function ensureCountry(InsuranceCompany $insuranceCompany): InsuranceAgency
    {
        return $this->ensure([
            'parent_id' => null,
            'insurance_company_id' => $insuranceCompany->getKey(),
            'level' => InsuranceLevel::Country->value,
        ], [
            'type' => InsuranceAgencyType::Legal->value,
            // "شرکت {insurer}", unless the insurer's name already starts with it ("شرکت بیمه ایران").
            'company_name' => str_starts_with(trim($insuranceCompany->name), 'شرکت') ? trim($insuranceCompany->name) : "شرکت {$insuranceCompany->name}",
        ]);
    }

    public function ensureProvince(InsuranceAgency $country, State $state): InsuranceAgency
    {
        return $this->ensure([
            'parent_id' => $country->getKey(),
            'insurance_company_id' => $country->insurance_company_id,
            'level' => InsuranceLevel::Province->value,
            'state_code' => $state->code,
        ], [
            'type' => InsuranceAgencyType::Legal->value,
            'company_name' => "سرپرستی استان {$state->name}",
        ]);
    }

    public function connectedInsurances(InsuranceAgency $agency): Collection
    {
        return Insurance::query()
            ->where('insurance_agency_id', $agency->getKey())
            ->with('company')
            ->orderBy('company_id')
            ->orderBy('id')
            ->get();
    }

    public function connectedCompanies(InsuranceAgency $agency): Collection
    {
        return Insurance::query()
            ->where('insurance_agency_id', $agency->getKey())
            ->with('company')
            ->get()
            ->groupBy('company_id')
            ->map(fn(Collection $insurances): array => [
                'company' => $insurances->first()->company,
                'insurances_count' => $insurances->count(),
                'active_count' => $insurances->where('insurance_agency_status', StatusEnum::ACTIVE)->count(),
            ])
            ->filter(fn(array $row): bool => $row['company'] !== null)
            ->sortBy(fn(array $row): string => (string)$row['company']->name)
            ->values();
    }

    public function unconnectedCompanies(InsuranceAgency $agency, ?string $search = null): Collection
    {
        return Company::query()
            ->whereDoesntHave('insurances', fn($query) => $query->where('insurance_agency_id', $agency->getKey()))
            ->when(filled($search), fn($query) => $query->where(fn($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('organization_code', 'like', "%{$search}%")))
            ->orderBy('name')
            ->get();
    }

    public function companyInsurances(InsuranceAgency $agency, int $companyId): Collection
    {
        return Insurance::query()
            ->where('company_id', $companyId)
            ->where('insurance_company_id', $agency->insurance_company_id)
            ->with(['insuranceCompany', 'agency'])
            ->orderBy('id')
            ->get();
    }

    public function findInsuranceOrFail(int $id): Insurance
    {
        return Insurance::query()->with('company')->findOrFail($id);
    }

    public function connectInsurance(InsuranceAgency $agency, Insurance $insurance): void
    {
        $insurance->update([
            'insurance_agency_id' => $agency->getKey(),
            'insurance_company_id' => $agency->insurance_company_id,
            'insurance_agency_status' => StatusEnum::ACTIVE->value,
        ]);
    }

    public function disconnectInsurance(Insurance $insurance): void
    {
        $insurance->update(['insurance_agency_status' => StatusEnum::INACTIVE->value]);
    }

    /**
     * The generated agent with these keys: created (active, with the given name) when there is none, brought back when it
     * was deleted. An existing one is left as the admin filled it in.
     *
     * @param array<string, mixed> $keys
     * @param array<string, mixed> $attributes
     */
    private function ensure(array $keys, array $attributes): InsuranceAgency
    {
        $query = InsuranceAgency::withTrashed();

        foreach ($keys as $column => $value) {
            $value === null ? $query->whereNull($column) : $query->where($column, $value);
        }

        $agency = $query->orderBy('id')->first();

        if ($agency === null) {
            return InsuranceAgency::query()->create([...$keys, ...$attributes, 'status' => StatusEnum::ACTIVE->value]);
        }

        $agency->trashed() && $agency->restore();

        return $agency;
    }
}
