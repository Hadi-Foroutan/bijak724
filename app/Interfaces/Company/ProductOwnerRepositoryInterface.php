<?php

namespace App\Interfaces\Company;

use App\Models\Company\ProductOwner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rules\Exists;

interface ProductOwnerRepositoryInterface
{
    /** @return Builder<ProductOwner> */
    public function query(int $companyId): Builder;

    /** @return Collection<int, ProductOwner>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): ProductOwner;

    public function findOrFail(int $companyId, int $id): ProductOwner;

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $id, array $data): ProductOwner;

    public function delete(int $companyId, int $id): void;

    public function existsRule(int $companyId, string $column = 'id'): Exists;
}
