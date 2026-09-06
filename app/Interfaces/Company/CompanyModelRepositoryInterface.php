<?php

namespace App\Interfaces\Company;

use App\Models\DynamicModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface CompanyModelRepositoryInterface
{
    public function query(int $companyId): Builder;

    /** @return Collection<int, DynamicModel>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    public function create(int $companyId, array $data): DynamicModel;

    public function findOrFail(int $companyId, int $id): DynamicModel;

    public function find(int $companyId, int $id): ?DynamicModel;

    public function update(int $companyId, int $id, array $data): DynamicModel;

    public function delete(int $companyId, int $id): void;
}
