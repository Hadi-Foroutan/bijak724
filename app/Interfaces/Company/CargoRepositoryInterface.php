<?php

namespace App\Interfaces\Company;

use App\Models\Company\Cargo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface CargoRepositoryInterface extends CompanyModelRepositoryInterface
{
    public function query(int $companyId): Builder;

    /** @return Collection<int, Cargo>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    public function create(int $companyId, array $data): Cargo;

    public function findOrFail(int $companyId, int $id): Cargo;

    public function update(int $companyId, int $id, array $data): Cargo;

    public function delete(int $companyId, int $id): void;
}
