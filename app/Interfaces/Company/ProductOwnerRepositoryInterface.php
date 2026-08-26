<?php

namespace App\Interfaces\Company;

use App\Models\Company\ProductOwner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface ProductOwnerRepositoryInterface extends CompanyModelRepositoryInterface
{
    public function query(int $companyId): Builder;

    /** @return Collection<int, ProductOwner>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    public function create(int $companyId, array $data): ProductOwner;

    public function findOrFail(int $companyId, int $id): ProductOwner;

    public function update(int $companyId, int $id, array $data): ProductOwner;

    public function delete(int $companyId, int $id): void;
}
