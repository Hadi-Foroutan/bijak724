<?php

namespace App\Services\Company;

use App\Interfaces\CompanyDataRepositoryInterface;
use App\Models\DynamicModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class CompanyDataService
{
    public function __construct(
        protected CompanyDataRepositoryInterface $repo,
        protected CompanyDataOwnerResolver $companyDataOwnerResolver,
    ) {}

    // ✅ یک شرکت
    public function list(int $companyId, string $table, array $filters = []): LengthAwarePaginator
    {
        $query = $this->repo->query($companyId, $table);

        return $this->applyFilters($query, $filters)->paginate();
    }

    // ✅ create generic
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

    // 🎯 فیلتر داینامیک
    private function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $field => $value) {
            $query->where($field, $value);
        }

        return $query;
    }
}
