<?php

namespace App\Services\Company;

use App\Interfaces\CompanyDataRepositoryInterface;
use App\Models\DynamicModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class CompanyDataService
{
    public function __construct(
        protected CompanyDataRepositoryInterface $repo,
        protected CompanyDataOwnerResolver $companyDataOwnerResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, DynamicModel>|LengthAwarePaginator
     */
    public function search(int $companyId, string $table, array $filters = []): Collection|LengthAwarePaginator
    {
        return $this->repo->search($companyId, $table, $filters);
    }

    /** @param array<string, mixed> $data */
    public function create(int $companyId, string $table, array $data): DynamicModel
    {
        return $this->repo->create($companyId, $table, $data);
    }

    public function table(int $companyId, string $table): string
    {
        return $this->repo->table($companyId, $table);
    }

    public function ownedExistsRule(int $companyId, string $table, string $column = 'id'): Exists
    {
        $rule = Rule::exists($this->table($companyId, $table), $column);

        if (! $this->companyDataOwnerResolver->isDataOwner($companyId)) {
            $rule->where('owner_company_id', $companyId);
        }

        return $rule;
    }
}
