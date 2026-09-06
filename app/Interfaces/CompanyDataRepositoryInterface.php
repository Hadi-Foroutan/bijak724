<?php

namespace App\Interfaces;

use App\Models\DynamicModel;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface CompanyDataRepositoryInterface
{
    public function table(int $companyId, string $table): string;

    public function query(int $companyId, string $tableKey, ?string $alias = null): Builder;

    /**
     * @param  array<string, mixed>  $filters
     * @param  null|Closure(Builder): (Builder|void)  $queryCallback
     * @return Collection<int, DynamicModel>|LengthAwarePaginator
     */
    public function search(
        int $companyId,
        string $tableKey,
        array $filters,
        ?Closure $queryCallback = null,
    ): Collection|LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $companyId, string $tableKey, array $data): DynamicModel;
}
