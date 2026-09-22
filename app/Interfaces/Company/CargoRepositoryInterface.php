<?php

namespace App\Interfaces\Company;

use App\Models\Company\Cargo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface CargoRepositoryInterface
{
    /** @return Builder<Cargo> */
    public function query(int $companyId): Builder;

    /** @return Collection<int, Cargo>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): Cargo;

    public function findOrFail(int $companyId, int $id): Cargo;

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $id, array $data): Cargo;

    public function delete(int $companyId, int $id): void;
}
