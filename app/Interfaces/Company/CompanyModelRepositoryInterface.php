<?php

namespace App\Interfaces\Company;

use App\Models\DynamicModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rules\Exists;

/** @template TModel of DynamicModel */
interface CompanyModelRepositoryInterface
{
    /** @return Builder<TModel> */
    public function query(int $companyId): Builder;

    /** @return Collection<int, TModel>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    /** @return TModel */
    public function create(int $companyId, array $data): DynamicModel;

    /** @return TModel */
    public function findOrFail(int $companyId, int $id): DynamicModel;

    /** @return TModel|null */
    public function find(int $companyId, int $id): ?DynamicModel;

    /** @return TModel */
    public function update(int $companyId, int $id, array $data): DynamicModel;

    public function delete(int $companyId, int $id): void;

    public function exists(int $companyId, int $id): bool;

    public function existsRule(int $companyId, string $column = 'id'): Exists;
}
