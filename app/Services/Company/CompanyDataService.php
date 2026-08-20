<?php

namespace App\Services\Company;

use App\Interfaces\CompanyDataRepositoryInterface;
use App\Models\DynamicModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CompanyDataService
{
    public function __construct(
        protected CompanyDataRepositoryInterface $repo
    )
    {
    }

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

    // 🎯 فیلتر داینامیک
    private function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $field => $value) {
            $query->where($field, $value);
        }

        return $query;
    }
}
