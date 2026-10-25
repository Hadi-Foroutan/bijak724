<?php

namespace App\Interfaces\Company;

use App\Models\Company\Driver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface DriverRepositoryInterface
{
    /** @return Builder<Driver> */
    public function query(int $companyId): Builder;

    /** @return Collection<int, Driver>|LengthAwarePaginator */
    public function search(int $companyId, array $filters): Collection|LengthAwarePaginator;

    /** @param array<string, mixed> $data */
    public function create(int $companyId, array $data): Driver;

    public function findOrFail(int $companyId, int $id): Driver;

    /** @param array<string, mixed> $data */
    public function update(int $companyId, int $id, array $data): Driver;

    public function delete(int $companyId, int $id): void;

    public function exists(int $companyId, int $id): bool;

    public function findByNationalCode(int $companyId, string $nationalCode): Driver;

}
